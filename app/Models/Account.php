<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Libraries\EndpointHelper;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use DateTimeInterface;
use App\Models\Log;

class Account extends Model
{
    protected $fillable = ['identity_id','system_id','account_id','status','account_attributes'];
    protected $casts = ['system_id'=>'string','account_attributes'=>'array'];

    public function system(){
        return $this->belongsTo(System::class);
    }

    public function identity(){
        return $this->belongsTo(Identity::class);
    }

    public function mark_disabled() {
        $log = new Log([
            'action'=>'disable',
            'identity_id'=>$this->identity_id,
            'type'=>'account',
            'type_id'=>$this->system_id,
            'data'=>$this->account_id,
            'actor_identity_id'=>isset(Auth::user()->id)?Auth::user()->id:null
        ]);
        $log->save();

        $this->status = 'disabled';
        $this->save();
    }

    public function mark_deleted() {
        $log = new Log([
            'action'=>'delete',
            'identity_id'=>$this->identity_id,
            'type'=>'account',
            'type_id'=>$this->system_id,
            'data'=>$this->account_id,
            'actor_identity_id'=>isset(Auth::user()->id)?Auth::user()->id:null
        ]);
        $log->save();

        $this->status = 'deleted';
        $this->save();
    }

    public function mark_restored() {
        $log = new Log([
            'action'=>'restore',
            'identity_id'=>$this->identity_id,
            'type'=>'account',
            'type_id'=>$this->system_id,
            'data'=>$this->account_id,
            'actor_identity_id'=>isset(Auth::user()->id)?Auth::user()->id:null
        ]);
        $log->save();

        $this->status = 'active';
        $this->save();
    }

    public function get_info() {
        $this->info = $this->sync('info');
    }

    public function sync($action) {
        $identity = Identity::where('id',$this->identity_id)->first();
        $myidentity = $identity->get_api_identity($this->system_id);

        $m = new \Mustache_Engine;
        $mysystem = System::where('id',$this->system_id)->first();
        $error = 'API Endpoint Misconfiguration Error';
        if (!is_null($mysystem) && isset($mysystem->config) && isset($mysystem->config->api) && isset($mysystem->config->api->$action)) {
            $action_definition = $mysystem->config->api->$action;
            if ($action_definition->enabled == true) {
                $endpoint = Endpoint::where('id',$action_definition->endpoint)->first();
                $myidentity['account'] = $this->only('account_id','status','account_attributes');
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

    protected static function booted() {
        static::saving(function ($account) {
            if (!isset($account->account_id) || is_null($account->account_id) || $account->account_id == '') {
                abort(400,'Account ID Cannot be Blank');
            }
        });
    }

}
