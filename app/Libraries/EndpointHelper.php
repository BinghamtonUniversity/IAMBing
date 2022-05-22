<?php

namespace App\Libraries;

use App\Libraries\HTTPHelper;

class EndpointHelper {
    
    static function http_request_maker($endpoint,$config,$data,$url){
        $httpHelper = new HTTPHelper();
        if ($endpoint->config->type == 'http_no_auth') {
            return $httpHelper->http_fetch(['url'=>$endpoint->config->url, 'verb'=>$config->verb]);
        } else if ($endpoint->config->type == 'http_basic_auth') {
            $http_config = [
                'url'  => $url,
                'verb' => $config->verb,
                'data'=>$data,
                'username' => $endpoint->config->username,
                'password' => $endpoint->getSecret(),
            ];
            if (isset($endpoint->config->content_type) && $endpoint->config->content_type !== '') {
                $http_config['content_type'] = $endpoint->config->content_type;
            }
            if (isset($endpoint->config->timeout) && $endpoint->config->timeout !== '') {
                $http_config['timeout'] = $endpoint->config->timeout;
            }
            if (isset($endpoint->config->headers) && is_array($endpoint->config->headers)) {
                $http_config['headers'] = $endpoint->config->headers;
            }
            return $httpHelper->http_fetch($http_config);
            
        } else {
            abort(505,'Authentication Type Not Supported');
        }
    }
}