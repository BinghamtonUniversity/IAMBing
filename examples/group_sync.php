<?php
chdir(dirname(__file__));

include_once('../app/Libraries/HTTPHelper.php');
use App\Libraries\HTTPHelper;

class IAMBingGroupSync
{
    static private $users = [
        [
            'ids'=>['bnumber'=>'B005058934'],
            'first_name'=>'Tim',
            'last_name'=>'Cortesi',
        ],
        [
            'ids'=>['bnumber'=>'B00123467'],
            'first_name'=>'John',
            'last_name'=>'Doe',
        ],
        [
            'ids'=>['bnumber'=>'B00123234'],
            'first_name'=>'Tony',
            'last_name'=>'Stark',
        ],
        [
            'ids'=>['bnumber'=>'B0034346'],
            'first_name'=>'I Am',
            'last_name'=>'Groot',
        ],
    ];

    static private $groups = [
        'STUDENTS'=>[1,2],
        'STAFF'=>[2,3,4],
    ];

    static private $iambing_url = 'http://iambing.local:8000';
    static private $iambing_username = 'test';
    static private $iambing_password = 'test';

    static public function sync() {
        $httphelper = new HTTPHelper();
        $graphene_response = $httphelper->http_fetch([
            'url'  => self::$iambing_url.'/api/public/groups/Full%20Time%20Staff/members',
            'verb' => 'POST',
            'data' => ['users'=>self::$users,'id'=>'bnumber'],
            'username' => self::$iambing_username,
            'password' => self::$iambing_password,
            'headers' => ['Accept'=>'application/json'],
        ]);
        var_dump($graphene_response);
    }

}

IAMBingGroupSync::sync();