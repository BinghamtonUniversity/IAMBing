<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Libraries\HTTPHelper;
use Illuminate\Support\Arr;

class Account extends Model
{
    use SoftDeletes;
    protected $fillable = ['user_id','system_id','account_id','status'];

    public function system(){
        return $this->belongsTo(System::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function disable() {
        $this->status = 'disabled';
        $this->save();
    }

    public function get_info() {
        $this->info = $this->sync('info');
    }

    private function build_sync_user() {
        $myuser = User::where('id',$this->user_id)->with('user_entitlements')->first()->only([
            'first_name','last_name','attributes','entitlements','ids','default_username','default_email','id'
        ]);
        $group_ids = GroupMember::select('group_id')->where('user_id',$myuser['id'])->get()->pluck('group_id');
        $myuser['affiliations'] = Group::select('affiliation','order')->whereIn('id',$group_ids)->orderBy('order')->get()->pluck('affiliation')->unique()->values()->toArray();
        $myuser['primary_affiliation'] = isset($myuser['affiliations'][0])?$myuser['affiliations'][0]:null;
        return $myuser;
    }

    public function sync($action) {
        $myuser = $this->build_sync_user();
        $m = new \Mustache_Engine;
        $mysystem = System::where('id',$this->system_id)->first();
        if (isset($mysystem->config->actions) && is_array($mysystem->config->actions)) {
            $action_definition = Arr::first($mysystem->config->actions, function ($value, $key) use ($action) {
                return $value->action === $action;
            });
            if (!is_null($action_definition)) {
                $endpoint = Endpoint::where('id',$action_definition->endpoint)->first();
                $myuser['account'] = $this->only('account_id','status');
                $url = $m->render($endpoint->config->url.$action_definition->path, $myuser);   
                $http_helper = new HTTPHelper();
                $payload = [
                    'url'  => $url,
                    'verb' => $action_definition->verb,
                    'data' => $myuser,
                    'username' => $endpoint->config->username,
                    'password' => $endpoint->config->secret,
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
