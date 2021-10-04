<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Arr;
use App\Libraries\HttpHelper;

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
        return $this->belongsToMany(System::class,'accounts')->orderBy('name')->withPivot('username');
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
            $entitlements[] = $entitlement->name;
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
        $accounts = Account::where('username',$username)->get();
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

    public function add_account($system, $username = null) {
        $account = new Account(['user_id'=>$this->id,'system_id'=>$system->id]);
        if (!is_null($username)) {
            $account->username = $username;
        } else {
            $template = $system->config->default_account_id_template;
            $account->username = $this->username_generate($template);
        }
        $account->save();
        return $account;
    }

    public function recalculate_entitlements() {
        // TJC -- All of this should be moved to an observer!
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
        // Provision System Accounts for Unmet Entitlements
        $existing_user_entitlements = UserEntitlement::select('entitlement_id')->where('user_id',$user->id)->where('type','add')->get()->pluck('entitlement_id')->unique();
        $system_ids_needed = Entitlement::select('system_id')->whereIn('id',$existing_user_entitlements)->get()->pluck('system_id')->unique();
        $system_ids_has = Account::select('system_id')->where('user_id',$user->id)->get()->pluck('system_id')->unique();
        $diff = $system_ids_needed->diff($system_ids_has);
        foreach($diff as $system_id) {
            $system = System::where('id',$system_id)->first();
            $user->add_account($system);
        }
        // This code deletes any accounts for any systems
        $diff = $system_ids_has->diff($system_ids_needed);
        Account::where('user_id',$user->id)->whereIn('system_id',$diff)->delete();
        // END

        $myaccounts = Account::where('user_id',$user->id)->with('system')->get();
        $m = new \Mustache_Engine;

        $myuser = User::where('id',$user->id)->with('user_entitlements')->first()->only([
            'first_name','last_name','attributes','entitlements','ids','default_username','default_email','id'
        ]);
        $group_ids = GroupMember::select('group_id')->where('user_id',$myuser['id'])->get()->pluck('group_id');
        $myuser['affiliations'] = Group::select('affiliation','order')->whereIn('id',$group_ids)->orderBy('order')->get()->pluck('affiliation')->unique()->values()->toArray();
        $myuser['primary_affiliation'] = isset($myuser['affiliations'][0])?$myuser['affiliations'][0]:null;

        foreach($myaccounts as $myaccount) {
            $mysystem = $myaccount->system;
            if ($mysystem->name === 'BU') {
                $action_definition = Arr::first($mysystem->config->actions, function ($value, $key) {
                    return $value->action === 'update';
                });
                if (!is_null($action_definition)) {
                    $endpoint = Endpoint::where('id',$action_definition->endpoint)->first();
                    $myuser['account_id'] = $myaccount->username;
                    $url = $m->render($endpoint->config->url.$action_definition->path, $myuser);   
                    $http_helper = new HTTPHelper();
                    $payload = [
                        'url'  => $url,
                        'verb' => $action_definition->verb,
                        'data' => $myuser,
                        'username' => $endpoint->config->username,
                        'password' => $endpoint->config->secret,
                    ];
                    // dd($payload);
                    $response = $http_helper->http_fetch($payload);
                    dd($response);
                }
            }
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
