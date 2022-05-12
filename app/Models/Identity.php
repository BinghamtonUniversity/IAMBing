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

    protected $fillable = ['active','type','sponsored','default_username', 'default_email', 'ids', 'attributes', 'first_name', 'last_name', 'sponsor_identity_id'];
    protected $hidden = ['identity_unique_ids','identity_attributes', 'identity_permissions', 'password', 'remember_token','created_at','updated_at'];
    protected $appends = ['ids','permissions','attributes','entitlements'];
    protected $with = ['identity_unique_ids','identity_attributes','identity_permissions'];

    private $set_ids = null;
    private $set_attributes = null;

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
        return $this->belongsToMany(System::class,'accounts')->orderBy('name')->whereNull('deleted_at')->withPivot('id','account_id','status','override');
    }

    public function systems_with_accounts_history() {
        return $this->belongsToMany(System::class,'accounts')->orderBy('name')->withPivot('id','account_id','status','override','deleted_at');
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

    public function getAttributesAttribute() {
        $attributes = [];
        foreach($this->identity_attributes as $attribute) {
            if ($attribute['array']===true) {
                $attributes[$attribute['name']] = explode(',',$attribute['value']);
            } else {
                $attributes[$attribute['name']] = $attribute['value'];
            }
        }
        return $attributes;
    }

    public function setAttributesAttribute($attributes) {
        $this->set_attributes = $attributes;
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
        // Derive username
        $obj = [
            'first_name' => str_split(strtolower($this->first_name), 1),
            'last_name' => str_split(strtolower($this->last_name), 1),
            'iterator' => $iterator,
            'default_username' => $this->default_username,
            'ids'=>$this->ids,
            'attributes' => $this->attributes,
        ];
        $m = new \Mustache_Engine;
        return $m->render($template, $obj);
    }

    private function username_check_available($username) {
        $accounts = Account::where('account_id',$username)->get();
        $identities = Identity::where('default_username',$username)->get();
        $history = Log::where('data',$username)->get();
        
        if (count($accounts) > 0 || count($identities) > 0 || count($history)>0) {
            return false;
        }
        // Do an external lookup using API Endpoints
        $config = Configuration::where('name','username_availability')
                                    ->first();
        if(is_null($config)){
            abort(500,'Undefined configuration!');
        }
        $config = $config->config;
        $endpoint = Endpoint::where('id',$config->endpoint)->first();

        if(is_null($endpoint)){
            abort(404,'Endpoint not found!');
        }
        $url = $endpoint->config->url.$config->path;
        $response = EndpointHelper::http_request_maker($endpoint,$config,['username'=>$username],$url);
        
        if($response['code'] == $config->available_response){
            return true;
        }else if($response['code'] == $config->not_available_response){
            return false;
        }else{
            abort(500,'Unsupported response received from the server');
        }

        return true;
    }

    public function save_actions() {
        if(!is_null($this->set_ids) || !is_null($this->set_attributes)){
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
        if (isset($config) && sizeof($configs)>0 && !is_null($this->set_attributes)) {
            foreach($this->set_attributes as $name => $value) {
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
        // Create and Set New username
        if (($this->first_name !== '' && $this->last_name !== '' && $this->first_name !== null && $this->last_name !== null) &&
            (!isset($this->default_username) || $this->default_username === '' || $this->default_username === null)) {
            $is_taken = false;
            $iterator = 0;
            $configuration = Configuration::where('name','default_username_template')->first();
            if (!is_null($configuration)) {
                $template = $configuration->config;
            }
            do {
                $username = $this->username_generate($template, $iterator);
                if (!$this->username_check_available($username)) {
                    $is_taken = true;
                    $iterator++;
                } else {
                    break;
                }
            } while ($is_taken);
            $this->default_username = $username;
            $this->save();
        }
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
            $existing_account->restore();
            $existing_account->save();
            return $existing_account;
        }
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
                $identity_entitlement->update(['override'=>false,'exipration_date'=>null,'description'=>null,'override_identity_id'=>null]);
                if (!$calculated_entitlement_ids->contains($identity_entitlement->entitlement_id)) {
                    $log = new Log([
                        'action'=>'delete',
                        'identity_id'=>$identity_entitlement->identity_id,
                        'type'=>'entitlement',
                        'type_id'=>$identity_entitlement->entitlement_id,
                        'actor_identity_id'=>isset(Auth::user()->id)?Auth::user()->id:null
                    ]);
                    $log->save();

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
                $log = new Log([
                    'action'=>'add',
                    'identity_id'=>$entitlement->identity_id,
                    'type'=>'entitlement',
                    'type_id'=>$entitlement->entitlement_id,
                    'actor_identity_id'=>isset(Auth::user()->id)?Auth::user()->id:null
                ]);
                $log->save();

                $entitlement->update(['type'=>'add','override'=>false,'expiration_date'=>null,'description'=>null,'override_identity_id'=>null]);
            }
        }
        $this->sync_accounts();
    }

    public function sync_accounts() {
        $identity = $this;
        // Create New Accounts for Unmet Entitlements
        $existing_identity_entitlements = IdentityEntitlement::select('entitlement_id')->where('identity_id',$identity->id)->where('type','add')->get()->pluck('entitlement_id')->unique();
        $system_ids_needed = Entitlement::select('system_id')->whereIn('id',$existing_identity_entitlements)->get()->pluck('system_id')->unique();
        $system_ids_has = Account::select('system_id')->where('identity_id',$identity->id)->where('status','active')->get()->pluck('system_id')->unique();
        $diff = $system_ids_needed->diff($system_ids_has);
        foreach($diff as $system_id) {
            $overridden_acct = Account::where('identity_id',$identity->id)->where('system_id',$system_id)->where('override',true)->first();
            if (is_null($overridden_acct)) {
                $system = System::where('id',$system_id)->first();
                $myaccount = $identity->add_account($system);
                $myaccount->sync('create');
            }
        }

        // Delete accounts for Former Entitlements
        $diff = $system_ids_has->diff($system_ids_needed);
        $myaccounts_to_delete = Account::where('identity_id',$identity->id)->with('system')->whereIn('system_id',$diff)->get();
        foreach($myaccounts_to_delete as $myaccount) {
            if (!$myaccount->override) {
                if ($myaccount->system->onremove === 'delete') {
                    $myaccount->sync('delete');
                    $myaccount->delete();
                } else if ($myaccount->system->onremove === 'disable') {
                    $myaccount->sync('disable');
                    $myaccount->disable();
                }
            }
        }

        // Sync All Accounts with current attributes and entitlements
        $myaccounts = Account::where('identity_id',$identity->id)->with('system')->get();
        foreach($myaccounts as $myaccount) {
            $myaccount->sync('update');
        }

    }

    public function get_api_identity(){
        $affiliations = Group::select('affiliation','order')
        ->whereIn('id',$this->group_memberships->pluck('group_id'))
        ->orderBy('order')
        ->get()
        ->pluck('affiliation')
        ->unique()->values();
        $identity_account_systems = System::select('id','name')->whereIn('id',$this->accounts->pluck('system_id'))->get();
        return [
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
            'primary_affiliation' => isset($myidentity['affiliations'][0])?$myidentity['affiliations'][0]:null,
            'entitlements'=>$this->entitlements,
            'accounts'=>$this->accounts ->map(function($q) use ($identity_account_systems){
                return [
                'id'=>$q->id,
                'account_id'=>$q->account_id,
                'system_id'=>$q->system_id,
                'system_name'=>$identity_account_systems->where('id',$q->system_id)->first()->name
                ];
            }),
            'attributes'=>$this->attributes
            ];
    }

    protected static function booted()
    {
        static::created(function ($identity) {
            $identity->save_actions();
            // Create IAM ID by base36 encoding the identity id
            $identity->iamid = 'IAM'.strtoupper(base_convert($identity->id,10,36));
            $identity->save();
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
