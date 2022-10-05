<?php

namespace App\Models;

use App\Libraries\EndpointHelper;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Identity extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['active','type','sponsored','default_username', 'default_email', 'ids', 'additional_attributes', 'first_name', 'last_name', 'sponsor_identity_id'];
    protected $hidden = ['identity_unique_ids','identity_attributes', 'identity_permissions', 'password', 'remember_token','created_at','updated_at'];
    protected $appends = ['ids','permissions','additional_attributes','entitlements'];
    protected $with = ['identity_unique_ids','identity_attributes','identity_permissions'];

    private $set_ids = null;
    private $set_additional_attributes = null;

    public function group_memberships(){
        return $this->hasMany(GroupMember::class,'identity_id');
    }

    public function groups() {
        return $this->belongsToMany(Group::class,'group_members')->orderBy('order');
    }
    public function admin_groups(){
        return $this->hasMany(GroupAdmin::class,'identity_id');
    }

    public function identity_entitlements() {
        return $this->belongsToMany(Entitlement::class,'identity_entitlements')->withPivot('type','override','description','expire','expiration_date','sponsor_id');
    }

    public function identity_permissions(){
        return $this->hasMany(Permission::class,'identity_id');
    }

    public function accounts(){
        return $this->hasMany(Account::class,'identity_id');
    }

    public function sponsored_identities(){
        return $this->hasMany(SimpleIdentity::class,'sponsor_identity_id')->where('sponsored',true);
    }

    public function systems() {
        return $this->belongsToMany(System::class,'accounts')->orderBy('name')->whereNull('deleted_at')->withPivot('id','account_id','status');
    }

    public function systems_with_accounts_history() {
        return $this->belongsToMany(System::class,'accounts')->orderBy('name')->withPivot('id','account_id','status','deleted_at');
    }

    public function identity_unique_ids() {
        return $this->hasMany(IdentityUniqueID::class,'identity_id');
    }

    public function getIdsAttribute() {
        $ids = [];
        foreach($this->identity_unique_ids as $id) {
            $ids[$id['name']] = $id['value'];
        }
        return $ids;
    }

    public function getEntitlementsAttribute() {
        $entitlements = [];
        foreach($this->identity_entitlements as $entitlement) {
            if ($entitlement->pivot->type === 'add') {
                $entitlements[] = $entitlement->name;
            }
        }
        return $entitlements;
    }

    public function setIdsAttribute($ids) {
        $this->set_ids = $ids;
    }

    public function identity_attributes() {
        return $this->hasMany(IdentityAttribute::class,'identity_id');
    }

    public function getAdditionalAttributesAttribute() {
        $additional_attributes = [];
        foreach($this->identity_attributes as $attribute) {
            if ($attribute['array']===true) {
                $additional_attributes[$attribute['name']] = explode(',',$attribute['value']);
            } else {
                $additional_attributes[$attribute['name']] = $attribute['value'];
            }
        }
        return $additional_attributes;
    }

    public function setAdditionalAttributesAttribute($additional_attributes) {
        $this->set_additional_attributes = $additional_attributes;
    }

    // Converts Identity Permissions to Array
    public function getPermissionsAttribute() {
        $permissions = $this->identity_permissions()->get();
        $permissions_arr = [];
        foreach($permissions as $permission) {
            $permissions_arr[] = $permission->permission;
        }
        return $permissions_arr;
    }

    public function username_generate($template, $iterator = 0) {
        $obj = [
            'first_name' => str_split(preg_replace("/[^a-z]/",'',strtolower($this->first_name)), 1),
            'last_name' => str_split(preg_replace("/[^a-z]/",'',strtolower($this->last_name)), 1),
            'iterator' => $iterator,
            'default_username' => $this->default_username,
            'ids'=>$this->ids,
            'additional_attributes' => $this->additional_attributes,
        ];
        $empty_obj = [
            'first_name' => '',
            'last_name' => '',
            'iterator' => $iterator,
            'default_username' => '',
            'ids' => [],
            'additional_attributes' => [],
        ];
        $m = new \Mustache_Engine;
        $username = $m->render($template, $obj);
        $empty_username = $m->render($template, $empty_obj);
        if ($username == $empty_username) {
            abort(400,'Missing Required Fields for Username / Account ID Generation');
        }
        return $username;
    }

    private function username_check_available($username) {
        $accounts = Account::where('account_id',$username)->withTrashed()->get();
        $identities = Identity::where('default_username',$username)->get();
        $history = Log::where('data',$username)->get();
        $reserved_usernames = ReservedUsername::where('username',$username)->get();
        if (count($accounts) > 0 || count($identities) > 0 || count($history) > 0 || count($reserved_usernames) > 0) {
            return false;
        }
        // Do an external lookup using API Endpoints
        $config = Configuration::where('name','username_availability')->first();
        if(is_null($config)) {
            // Not Configured -- Skip Check and Continue
            return true;
        }
        $config = $config->config;
        $endpoint = Endpoint::where('id',$config->endpoint)->first();
        if(is_null($endpoint)){
            // Endpoint Not Exists -- Skip Check and Continue
            return true;
        }
        $url = $endpoint->config->url.$config->path;
        try {
            $response = EndpointHelper::http_request_maker($endpoint,$config,['username'=>$username],$url);
            if($response['code'] == $config->available_response) {
                return true;
            } else if($response['code'] == $config->not_available_response) {
                return false;
            }
        } catch (\Throwable $e) {
            // Failed when performing external check.  Assume Success and Continue
            return true;
        }
        return true;
    }

    private function save_actions() {
        if(!is_null($this->set_ids) || !is_null($this->set_additional_attributes)){
            $configs_res = Configuration::where('name','identity_attributes')
                                        ->orWhere('name','identity_unique_ids')
                                        ->get()->toArray();

            foreach ($configs_res as $conf){
                $config[$conf['name']] = Arr::pluck($conf['config'],'name');
            }
            $configs = Configuration::where('name','identity_attributes')
                                        ->orWhere('name','identity_unique_ids')
                                        ->get()->pluck('name');
        }
        // Set IDS and Attributes
        if (isset($config) && sizeof($configs)>0 && !is_null($this->set_ids)) {
            foreach($this->set_ids as $name => $value) {
                if(!in_array($name,$config['identity_unique_ids'])){
                    continue;
                }
                IdentityUniqueID::updateOrCreate(
                    ['identity_id'=>$this->id, 'name'=>$name],
                    ['value' => $value]
                );
            }
            $this->load('identity_unique_ids');
        }
        if (isset($config) && sizeof($configs)>0 && !is_null($this->set_additional_attributes)) {
            foreach($this->set_additional_attributes as $name => $value) {
                if(!in_array($name,$config['identity_attributes'])){
                    continue;
                }
                IdentityAttribute::updateOrCreate(
                    ['identity_id'=>$this->id, 'name'=>$name],
                    ['value' => is_array($value)?implode(',',$value):$value,'array'=>is_array($value)]
                );
            }
            $this->load('identity_attributes');
        }
        $must_save = false;
        // Create and Set New username
        if (($this->first_name !== '' && $this->last_name !== '' && $this->first_name !== null && $this->last_name !== null) &&
            (!isset($this->default_username) || $this->default_username === '' || $this->default_username === null)) {
            $is_taken = false;
            $iterator = 0;
            $default_username_configuration = Configuration::where('name','default_username_template')->first();
            if (!is_null($default_username_configuration)) {
                $default_username_template = $default_username_configuration->config;
            }
            do {
                $username = $this->username_generate($default_username_template, $iterator);
                if (!$this->username_check_available($username)) {
                    $is_taken = true;
                    $iterator++;
                } else {
                    break;
                }
            } while ($is_taken);
            $this->default_username = $username;
            $must_save = true;
        }
        if (!isset($this->default_email) || is_null($this->default_email) || $this->default_email === '') {
            $default_email_configuration = Configuration::where('name','default_email_domain')->first();
            if (!is_null($default_email_configuration)) {
                $default_email_domain = $default_email_configuration->config;
            }
            $this->default_email = $this->default_username.'@'.$default_email_domain;
            $must_save = true;
        }
        if (!isset($this->iamid) || is_null($this->iamid) || $this->iamid == '') {
            $this->iamid = 'IAM'.strtoupper(base_convert($this->id,10,36));
            $must_save = true;
        }
        if ($must_save == true) {
            $this->save();
        }
    }

    private function check_unique_id_collision() {
        if ($this->first_name == '' || $this->last_name == '' || is_null($this->first_name) || is_null($this->last_name)) {
            abort(400,'Identities must have a first and last name');
        }
        if (preg_replace("/[^a-z]/",'',strtolower($this->first_name)) == '' || preg_replace("/[^a-z]/",'',strtolower($this->last_name)) == '') {
            abort(400,'Identities must have a valid first and last name with alphabetic characters');
        }
        $ids = $this->set_ids;
        $no_ids = true;
        if (!is_array($ids)) {
            return false;
        }
        foreach($ids as $id_name => $id_value) {
            if ($id_value != '' && !is_null($id_value)) {
                $no_ids = false;
                break;
            }
        }
        if ($no_ids == true) {
            return false;
        }
        $q = IdentityUniqueID::select('id','identity_id','name','value');
        if (isset($this->id) && !is_null($this->id)) {
            $q->where('identity_id','!=',$this->id);
        }
        $q->where(function($q) use ($ids) {
            foreach($ids as $id_name => $id_value) {
                if (!is_null($id_value) && $id_value != '') {
                    $q->orWhere(function($q) use ($id_name,$id_value) {
                        $q->where('name', $id_name)->where('value',$id_value);
                    });
                }
            }
        });
        $collision = $q->first();
        if (!is_null($collision)) {
            abort(400,'Collision of '.$collision->name.' ('.$collision->value.') with identity ('.$collision->identity_id.')');
        }
        return false;
    }

    public function add_account($system, $account_id = null) {
        $account = new Account(['identity_id'=>$this->id,'system_id'=>$system->id]);
        if (!is_null($account_id)) {
            $account->account_id = $account_id;
        } else {
            $template = $system->default_account_id_template;
            $account->account_id = $this->username_generate($template);
        }
        $existing_account = Account::where('identity_id',$this->id)->where('system_id',$system->id)->where('account_id',$account->account_id)->withTrashed()->first();
        if (is_null($existing_account)) {
            $account->save();
            return $account;
        } else {
            $existing_account->status = 'active';
            if ($existing_account->trashed()) {
                $existing_account->restore();
            }
            $existing_account->save();
            return $existing_account;
        }
    }

    // This needs to point to a particilar date in the future
    public function future_impact_email($end_user_visible_only=true) {
        $identity = $this;
        $impact = $this->future_impact_calculate($end_user_visible_only);
        if ($impact === false) {
            return false;
        }
        $config = Configuration::select('config')->where('name','action_queue_remove_email')->first();
        if (is_null($config)) {
            return false;
        }
        $m = new \Mustache_Engine;
        $email = [];
        $email['body'] = preg_replace("/\n\n\n+/","\n\n",$m->render($config->config->body, ['identity'=>$this,'impact'=>$impact]));
        $email['subject'] = $m->render($config->config->subject, ['identity'=>$this,'impact'=>$impact]);
        $recipients_string = $m->render($config->config->recipients, $this);
        $recipients = array_filter(explode(',',str_replace(' ','',$recipients_string)));
        $email['to'] = [];
        foreach($recipients as $recipient) {
            $email['to'][] = ['email'=>$recipient,'name'=>$identity->first_name.' '.$identity->last_name];
        }
        return $email;
    }

    public function future_impact_calculate($end_user_visible_only=true) {
        $identity = $this;
        $future_groups_remove  = GroupActionQueue::select('group_id','scheduled_date')->where('identity_id',$identity->id)->where('action','remove')->get();
        if ($future_groups_remove->count() === 0) {
            return false; // Exist prematurely if no impact is found.
        }
        $current_groups = Group::select('id','name')->whereHas('members',function($q) use ($identity) {
            $q->select('group_id')->where('identity_id',$identity->id);
        })->get();
        $current_groups_obj = $current_groups->values()->mapWithKeys(function ($value, $key) {
            return [preg_replace('/\s+/','_',preg_replace("/[^a-z]/",' ',strtolower($value['name']))) => true];
        });

        $future_groups_add = GroupActionQueue::select('group_id','scheduled_date')->where('identity_id',$identity->id)->where('action','add')->get();
        $future_group_ids = $current_groups->pluck('id')->concat($future_groups_add->pluck('group_id'))->unique()->diff($future_groups_remove->pluck('group_id'));
        $lost_group_ids = $current_groups->pluck('id')->diff($future_group_ids);

        $lost_groups = $current_groups->whereIn('id',$lost_group_ids);
        $lost_groups_obj = $lost_groups->values()->mapWithKeys(function ($value, $key) {
            return [preg_replace('/\s+/', '_', preg_replace("/[^a-z]/", ' ', strtolower($value['name']))) => true];
        });
        // Include Scheduled Deletion Date in Lost Groups Array
        $lost_groups = $lost_groups->map(function($item,$key) use ($future_groups_remove) {
            $action = $future_groups_remove->firstWhere('group_id',$item->id);
            if (!is_null($action) && !is_null($action->scheduled_date)) {
                $item->scheduled_date = $action->scheduled_date->format('n/j/Y');
            }
            return $item;
        });

        // Possibly should look at entitement overrides, and override dates?
        $current_entitlements = Entitlement::select('id','name','end_user_visible')->whereHas('identity_entitlements2',function($q) use ($identity) {
            $q->select('entitlement_id')->where('identity_id',$identity->id)->where('override',false);
        })->get();
        if ($end_user_visible_only === true) { // Only return End User Visible Entitlements
            $current_entitlements = $current_entitlements->where('end_user_visible',true);
        }
        $future_entitlement_ids = GroupEntitlement::select('entitlement_id')->whereIn('group_id',$future_group_ids)->distinct()->get()->pluck('entitlement_id');
        $lost_entitlement_ids = $current_entitlements->pluck('id')->diff($future_entitlement_ids);
        if ($lost_entitlement_ids->count() === 0) {
            return false; // Exist prematurely if no impact is found.
        }

        $impacted_systems = System::select('id','name')->whereHas('entitlements',function($q) use ($lost_entitlement_ids) {
            $q->select('system_id')->whereIn('id',$lost_entitlement_ids);
        })->distinct()->get();
        $impacted_accounts = Account::select('id','account_id','system_id')->with(['system' => function ($query) {
            $query->select('id', 'name');
        }])->where('identity_id',$identity->id)->whereIn('system_id',$impacted_systems->pluck('id'))->distinct()->get();

        $lost_entitlements = $current_entitlements->whereIn('id',$lost_entitlement_ids)->values();
        $lost_entitlements_obj = $lost_entitlements->mapWithKeys(function ($value, $key) {
            return [preg_replace('/\s+/', '_', preg_replace("/[^a-z]/", ' ', strtolower($value['name']))) => true];
        });
        
        return [
            'current_groups' => $current_groups->toArray(),
            'current_groups_obj' => $current_groups_obj,
            'lost_groups' => $lost_groups->toArray(), 
            'lost_groups_obj' => $lost_groups_obj, 
            'lost_entitlements' => $lost_entitlements->toArray(),
            'lost_entitlements_obj' => $lost_entitlements_obj,
            'impacted_accounts' => $impacted_accounts->values()->toArray(),
        ];
    }

    public function recalculate_entitlements() {
        // This code adds new accounts for any new systems
        $identity = $this;
        $group_ids = GroupMember::select('group_id')->where('identity_id',$identity->id)->get()->pluck('group_id');
        $calculated_entitlement_ids = GroupEntitlement::select('entitlement_id')->whereIn('group_id',$group_ids)->get()->pluck('entitlement_id')->unique();

        // Check to see if calculated entitlements match enforced entitlements
        $existing_identity_entitlements = IdentityEntitlement::where('identity_id',$identity->id)->get();
        foreach($existing_identity_entitlements as $identity_entitlement) {
            if (!$identity_entitlement->override || ($identity_entitlement->expire === true && $identity_entitlement->expiration_date->isPast())) {
                $identity_entitlement->update(['override'=>false,'expiration_date'=>null,'description'=>null,'override_identity_id'=>null]);
                if (!$calculated_entitlement_ids->contains($identity_entitlement->entitlement_id)) {
                    $identity_entitlement->delete();
                }
            }
        }
        foreach($calculated_entitlement_ids as $calculated_entitlement_id) {
            $entitlement = $existing_identity_entitlements->firstWhere('entitlement_id',$calculated_entitlement_id);
            if (is_null($entitlement)) {
                $new_identity_entitlement = new IdentityEntitlement(['identity_id'=>$identity->id,'entitlement_id'=>$calculated_entitlement_id]);
                $new_identity_entitlement->save();
            } else if ((!$entitlement->override || ($identity_entitlement->expire === true && $entitlement->expiration_date->isPast())) && $entitlement->type === 'remove') {
                $entitlement->update(['type'=>'add','override'=>false,'expiration_date'=>null,'description'=>null,'override_identity_id'=>null]);
            }
        }
        return $this->sync_accounts();
    }

    public function sync_accounts() {
        $sync_errors = [];
        $identity = $this;
        // Create New Accounts for Unmet Entitlements
        $existing_identity_entitlements = IdentityEntitlement::select('entitlement_id')->where('identity_id',$identity->id)->where('type','add')->get()->pluck('entitlement_id')->unique();
        $system_ids_needed = Entitlement::select('system_id')->whereIn('id',$existing_identity_entitlements)->get()->pluck('system_id')->unique();
        $system_ids_has = Account::select('system_id')->where('identity_id',$identity->id)->whereIn('status',['active','sync_error'])->get()->pluck('system_id')->unique();
        $diff = $system_ids_needed->diff($system_ids_has);
        $processed_account_ids = [];
        foreach($diff as $system_id) {
            $system = System::where('id',$system_id)->first();
            $myaccount = $identity->add_account($system);
            $processed_account_ids[] = $myaccount->id;
            $resp = $myaccount->sync('create');
            if (array_key_exists('error',$resp)) {
                $sync_errors[$myaccount->account_id] = $resp['error'];
            } else {
                if ($myaccount->status == 'sync_error') {
                    $myaccount->status = 'active';
                    $myaccount->save();
                }
            }
        }

        // Delete accounts for Former Entitlements
        $diff = $system_ids_has->diff($system_ids_needed);
        $myaccounts_to_delete = Account::where('identity_id',$identity->id)->with('system')->whereIn('system_id',$diff)->get();
        foreach($myaccounts_to_delete as $myaccount) {
            $processed_account_ids[] = $myaccount->id;
            if ($myaccount->system->onremove === 'delete') {
                $resp = $myaccount->sync('delete');
                if (array_key_exists('error',$resp)) {
                    $sync_errors[$myaccount->account_id] = $resp['error'];
                } else {
                    $myaccount->delete();
                }
            } else if ($myaccount->system->onremove === 'disable') {
                $resp = $myaccount->sync('disable');
                if (array_key_exists('error',$resp)) {
                    $sync_errors[$myaccount->account_id] = $resp['error'];
                } else {
                    $myaccount->disable();
                }
            }
        }

        // Sync All Accounts with current attributes and entitlements
        $myaccounts = Account::where('identity_id',$identity->id)->with('system')->get();
        foreach($myaccounts as $myaccount) {
            if (in_array($myaccount->id,$processed_account_ids)) {
                continue; // Skip Accounts that we just added or deleted!
            }
            $resp = $myaccount->sync('update');
            if (array_key_exists('error',$resp)) {
                $sync_errors[$myaccount->account_id] = $resp['error'];
            } else {
                if ($myaccount->status == 'sync_error') {
                    $myaccount->status = 'active';
                    $myaccount->save();
                }
            }
        }
        if (count($sync_errors)>0) {
            return ['errors'=>$sync_errors];
        } else {
            return true;
        }
    }

    public function get_api_identity(){
        $affiliations = Group::select('affiliation','order')
            ->whereIn('id',$this->group_memberships->pluck('group_id'))
            ->whereNotNull('affiliation')
            ->orderBy('order')
            ->get()
            ->pluck('affiliation')
            ->unique()->values()->toArray();
        $identity_account_systems = System::select('id','name')->whereIn('id',$this->accounts->pluck('system_id'))->get();
        if ($this->sponsored == true) {
            $sponsor_info = Identity::where('id',$this->sponsor_identity_id)->first()->only(['first_name','last_name','ids','iamid']);
        }
        $data = [
            'id'=>$this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'ids'=>$this->ids,
            'default_email'=>$this->default_email,
            "default_username"=>$this->default_username,
            'affiliations' => $affiliations,
            'group_memberships'=>$this->groups->map(function($q){
                return [
                    'id'=>$q->id,
                    'slug'=>$q->slug,
                    'name'=>$q->name
                ];
            }),
            'primary_affiliation' => isset($affiliations[0])?$affiliations[0]:null,
            'entitlements'=>$this->entitlements,
            'accounts'=>$this->accounts->map(function($q) use ($identity_account_systems){
                return [
                    'id'=>$q->id,
                    'account_id'=>$q->account_id,
                    'system_id'=>$q->system_id,
                    'system_name'=>$identity_account_systems->where('id',$q->system_id)->first()->name,
                    'account_attributes'=>$q->account_attributes,
                ];
            }),
            'additional_attributes'=>$this->additional_attributes,
            'sponsor'=>$this->sponsored?$sponsor_info:false,
        ];
        return $data;
    }

    protected static function booted()
    {
        static::creating(function ($identity) {
            $identity->check_unique_id_collision();
        });
        static::created(function ($identity) {
            $identity->save_actions();
        });
        static::updating(function ($identity) {
            $identity->check_unique_id_collision();
        });
        static::updated(function ($identity) {
            $identity->save_actions();
        });
        static::saved(function($identity){
            $identity->save_actions();
        });
    }

    public function is_group_admin($group_id=null){
        if (is_null($group_id)){
            return (bool)GroupAdmin::where('identity_id',$this->id)->first();
        }
        return (bool)GroupAdmin::where('identity_id',$this->id)
            ->where('group_id',$group_id)
            ->first();
    }

    public function send_email_check() {
        /* Send email if MAIL_LIMIT_SEND is false (Not limiting emails) */
        if (config('mail.limit_send') === false) {
            return true;
        }
        /* Send email if MAIL_LIMIT_SEND is true, and MAIL_LIMIT_ALLOW contains user's email address */
        if (config('mail.limit_send') === true && in_array($this->default_email,config('mail.limit_allow'))) {
            return true;
        }
        /* Otherwise don't send email */
        return false;
    }
}
