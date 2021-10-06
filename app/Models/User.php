<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Arr;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['active','sponsored','default_username', 'default_email', 'ids', 'attributes', 'first_name', 'last_name', 'sponsor_user_id'];
    protected $hidden = ['user_unique_ids','user_attributes', 'user_permissiins', 'password', 'remember_token','created_at','updated_at'];
    protected $appends = ['ids','permissions','attributes','entitlements'];
    protected $with = ['user_unique_ids','user_attributes','user_permissions'];

    private $set_ids = null;
    private $set_attributes = null;

    public function group_memberships(){
        return $this->hasMany(GroupMember::class,'user_id');
    }

    public function groups() {
        return $this->belongsToMany(Group::class,'group_members')->orderBy('order');
    }

    public function user_entitlements() {
        return $this->belongsToMany(Entitlement::class,'user_entitlements')->withPivot('type','override','override_description','override_expiration');
    }

    public function user_permissions(){
        return $this->hasMany(Permission::class,'user_id');
    }

    public function accounts(){
        return $this->hasMany(Account::class,'user_id');
    }

    public function sponsored_users(){
        return $this->hasMany(SimpleUser::class,'sponsor_user_id')->where('sponsored',true);
    }

    public function systems() {
        return $this->belongsToMany(System::class,'accounts')->orderBy('name')->whereNull('deleted_at')->withPivot('account_id','status');
    }

    public function user_unique_ids() {
        return $this->hasMany(UserUniqueID::class,'user_id');
    }

    public function getIdsAttribute() {
        $ids = [];
        foreach($this->user_unique_ids as $id) {
            $ids[$id['name']] = $id['value'];
        }
        return $ids;
    }

    public function getEntitlementsAttribute() {
        $entitlements = [];
        foreach($this->user_entitlements as $entitlement) {
            if ($entitlement->pivot->type === 'add') {
                $entitlements[] = $entitlement->name;
            }
        }
        return $entitlements;
    }

    public function setIdsAttribute($ids) {
        $this->set_ids = $ids;
    }

    public function user_attributes() {
        return $this->hasMany(UserAttribute::class,'user_id');
    }

    public function getAttributesAttribute() {
        $attributes = [];
        foreach($this->user_attributes as $attribute) {
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

    // Converts User Permissions to Array
    public function getPermissionsAttribute() {
        $permissions = $this->user_permissions()->get();
        $permissions_arr = [];
        foreach($permissions as $permission) {
            $permissions_arr[] = $permission->permission;
        }
        return $permissions_arr;
    }

    public function username_generate($template, $iterator = 0) {
        // Derive Username
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
        $users = User::where('default_username',$username)->get();
        if (count($accounts) > 0 || count($users) > 0) {
            return false;
        }
        // Do an external lookup using API Endpoints
        return true;
    }

    public function save_actions() {
        // Set IDS and Attributes
        if (!is_null($this->set_ids)) {
            foreach($this->set_ids as $name => $value) {
                UserUniqueID::updateOrCreate(
                    ['user_id'=>$this->id, 'name'=>$name],
                    ['value' => $value]
                );
            }    
            $this->load('user_unique_ids');    
        }
        if (!is_null($this->set_attributes)) {
            foreach($this->set_attributes as $name => $value) {
                UserAttribute::updateOrCreate(
                    ['user_id'=>$this->id, 'name'=>$name],
                    ['value' => is_array($value)?implode(',',$value):$value,'array'=>is_array($value)]
                );
            }   
            $this->load('user_attributes');    
        }
        // Create and Set New Username
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
        $account = new Account(['user_id'=>$this->id,'system_id'=>$system->id]);
        if (!is_null($account_id)) {
            $account->account_id = $account_id;
        } else {
            $template = $system->default_account_id_template;
            $account->account_id = $this->username_generate($template);
        }
        $existing_account = Account::where('user_id',$this->id)->where('system_id',$system->id)->where('account_id',$account->account_id)->withTrashed()->first();
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
        $user = $this;
        $group_ids = GroupMember::select('group_id')->where('user_id',$user->id)->get()->pluck('group_id');
        $calculated_entitlement_ids = GroupEntitlement::select('entitlement_id')->whereIn('group_id',$group_ids)->get()->pluck('entitlement_id')->unique();
        
        // Check to see if calculated entitlements match enforced entitlements
        $existing_user_entitlements = UserEntitlement::where('user_id',$user->id)->get();
        foreach($existing_user_entitlements as $user_entitlement) {
            if (!$user_entitlement->override || $user_entitlement->override_expiration->isPast()) {
                $user_entitlement->update(['override'=>false,'override_expiration'=>null,'override_description'=>null,'override_user_id'=>null]);
                if (!$calculated_entitlement_ids->contains($user_entitlement->entitlement_id)) {
                    $user_entitlement->delete();
                }
            }
        }
        foreach($calculated_entitlement_ids as $calculated_entitlement_id) {
            $entitlement = $existing_user_entitlements->firstWhere('entitlement_id',$calculated_entitlement_id);
            if (is_null($entitlement)) {
                $new_user_entitlement = new UserEntitlement(['user_id'=>$user->id,'entitlement_id'=>$calculated_entitlement_id]);
                $new_user_entitlement->save();
            } else if ((!$entitlement->override || $entitlement->override_expiration->isPast()) && $entitlement->type === 'remove') {
                $entitlement->update(['type'=>'add','override'=>false,'override_expiration'=>null,'override_description'=>null,'override_user_id'=>null]);
            }
        }
        $this->sync_accounts();
    }

    public function sync_accounts() {
        $user = $this;
        // Create New Accounts for Unmet Entitlements
        $existing_user_entitlements = UserEntitlement::select('entitlement_id')->where('user_id',$user->id)->where('type','add')->get()->pluck('entitlement_id')->unique();
        $system_ids_needed = Entitlement::select('system_id')->whereIn('id',$existing_user_entitlements)->get()->pluck('system_id')->unique();
        $system_ids_has = Account::select('system_id')->where('user_id',$user->id)->where('status','active')->get()->pluck('system_id')->unique();
        $diff = $system_ids_needed->diff($system_ids_has);
        // dd($diff);
        foreach($diff as $system_id) {
            $system = System::where('id',$system_id)->first();
            $myaccount = $user->add_account($system);
            $myaccount->sync('create');
        }

        // Delete accounts for Former Entitlements
        $diff = $system_ids_has->diff($system_ids_needed);
        $myaccounts_to_delete = Account::where('user_id',$user->id)->with('system')->whereIn('system_id',$diff)->get();
        foreach($myaccounts_to_delete as $myaccount) {
            if ($myaccount->system->onremove === 'delete') {
                $myaccount->sync('delete');
                $myaccount->delete();
            } else if ($myaccount->system->onremove === 'disable') {
                $myaccount->sync('disable');
                $myaccount->disable();
            }
        }

        // Sync All Accounts with current attributes and entitlements
        $myaccounts = Account::where('user_id',$user->id)->with('system')->get();
        // dd($myaccounts->toArray());
        foreach($myaccounts as $myaccount) {
            // if ($myaccount->system->name === 'BU') {
                // if ($myaccount->status === 'active') {
                    $myaccount->sync('update');
                // }
            // }
        }
    }

    protected static function booted()
    {
        static::created(function ($user) {
            $user->save_actions();
        });
        static::updated(function ($user) {
            $user->save_actions();
        });
    }
}
