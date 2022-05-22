<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Libraries\EndpointHelper;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use DateTimeInterface;

class Account extends Model
{
    use SoftDeletes;
    protected $fillable = ['identity_id','system_id','account_id','status'];
    protected $casts = ['system_id'=>'string'];

    public function system(){
        return $this->belongsTo(System::class);
    }

    public function identity(){
        return $this->belongsTo(Identity::class);
    }

    public function disable() {
        $this->status = 'disabled';
        $log = new Log([
            'action'=>'disable',
            'identity_id'=>$this->identity_id,
            'type'=>'account',
            'type_id'=>$this->system_id,
            'data'=>$this->account_id,
            'actor_identity_id'=>isset(Auth::user()->id)?Auth::user()->id:null
        ]);

        $log->save();
        $this->save();
    }

    public function get_info() {
        $this->info = $this->sync('info');
    }

    // private function build_sync_identity() {
    //     $myidentity = Identity::where('id',$this->identity_id)->with('identity_entitlements')->first()->only([
    //         'first_name','last_name','attributes','entitlements','ids','default_username','default_email','id'
    //     ]);
    //     $group_ids = GroupMember::select('group_id')->where('identity_id',$myidentity['id'])->get()->pluck('group_id');
    //     $myidentity['affiliations'] = Group::select('affiliation','order')->whereIn('id',$group_ids)->orderBy('order')->get()->pluck('affiliation')->unique()->values()->toArray();
    //     $myidentity['primary_affiliation'] = isset($myidentity['affiliations'][0])?$myidentity['affiliations'][0]:null;
    //     return $myidentity;
    // }

    public function sync($action) {
        $identity = Identity::where('id',$this->identity_id)->first();
        $myidentity = $identity->get_api_identity();

        $m = new \Mustache_Engine;
        $mysystem = System::where('id',$this->system_id)->first();
        $error = 'API Endpoint Misconfiguration Error';
        if (isset($mysystem->config->actions) && is_array($mysystem->config->actions)) {
            $action_definition = Arr::first($mysystem->config->actions, function ($value, $key) use ($action) {
                return $value->action === $action;
            });
            if (!is_null($action_definition)) {
                $endpoint = Endpoint::where('id',$action_definition->endpoint)->first();
                $myidentity['account'] = $this->only('account_id','status');
                $url = $m->render($endpoint->config->url.$action_definition->path, $myidentity);   
                $response = EndpointHelper::http_request_maker($endpoint,$action_definition,$myidentity,$url);
                if ($response['code'] == $action_definition->response_code) {
                    return Arr::only($response, ['code', 'content']);
                } else {
                    $error = $response;
                }
            }
        }
        if ($action != 'info') {
            $this->status = 'sync_error';
            $this->save();
        }
        return ['error'=>$error];       
    }

    protected function serializeDate(DateTimeInterface $date) {
        return $date->format('Y-m-d H:i:s a');
    }
}
