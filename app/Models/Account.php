<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Libraries\HTTPHelper;
use Illuminate\Support\Arr;

class Account extends Model
{
    use SoftDeletes;
    protected $fillable = ['identity_id','system_id','account_id','status','override','override_description','override_identity_id'];
    protected $casts = ['override'=>'boolean','system_id'=>'string'];

    public function system(){
        return $this->belongsTo(System::class);
    }

    public function identity(){
        return $this->belongsTo(Identity::class);
    }

    public function override_identity(){
        return $this->belongsTo(SimpleIdentity::class,'override_identity_id');
    }

    public function disable() {
        $this->status = 'disabled';
        $this->save();
    }

    public function get_info() {
        $this->info = $this->sync('info');
    }

    private function build_sync_identity() {
        $myidentity = Identity::where('id',$this->identity_id)->with('identity_entitlements')->first()->only([
            'first_name','last_name','attributes','entitlements','ids','default_username','default_email','id'
        ]);
        $group_ids = GroupMember::select('group_id')->where('identity_id',$myidentity['id'])->get()->pluck('group_id');
        $myidentity['affiliations'] = Group::select('affiliation','order')->whereIn('id',$group_ids)->orderBy('order')->get()->pluck('affiliation')->unique()->values()->toArray();
        $myidentity['primary_affiliation'] = isset($myidentity['affiliations'][0])?$myidentity['affiliations'][0]:null;
        return $myidentity;
    }

    public function sync($action) {
        $myidentity = $this->build_sync_identity();
        $m = new \Mustache_Engine;
        $mysystem = System::where('id',$this->system_id)->first();
        if (isset($mysystem->config->actions) && is_array($mysystem->config->actions)) {
            $action_definition = Arr::first($mysystem->config->actions, function ($value, $key) use ($action) {
                return $value->action === $action;
            });
            if (!is_null($action_definition)) {
                $endpoint = Endpoint::where('id',$action_definition->endpoint)->first();
                $myidentity['account'] = $this->only('account_id','status');
                $url = $m->render($endpoint->config->url.$action_definition->path, $myidentity);   
                $http_helper = new HTTPHelper();
                $payload = [
                    'url'  => $url,
                    'verb' => $action_definition->verb,
                    'data' => $myidentity,
                    'username' => $endpoint->config->username,
                    'password' => $endpoint->getSecret(),
                ];
                $response = $http_helper->http_fetch($payload);
                if ($response['code'] == $action_definition->response_code) {
                    return $response['content'];
                } else {
                    return $response;
                }
            }
        }
    }
}
