<?php
chdir(dirname(__file__));

include_once('../app/Libraries/HTTPHelper.php');
use App\Libraries\HTTPHelper;

class IAMBingBulkUpdateIdentities
{
    static public $identities = [
        [
            'ids'=>['bnumber'=>'B00450942'],
            'first_name'=>'Ali Kemal',
            'last_name'=>'Tanriverdi',
            'additional_attributes' => [
                'personal_emails' => ['a@b.c'],
            ]
        ],
        [
            'ids'=>['bnumber'=>'B00505893','suny_id'=>'12345'],
            'first_name'=>'Tim',
            'last_name'=>'Cortesi',
            'additional_attributes' => [
                'personal_emails' => ['tcortesi@gmail.com','tcortesi@escherlabs.com','another@one.com'],
                'department' => 'Some Department',
            ]
        ],
    ];

    static private $iambing_url = 'http://iambing.local:8000';
    static private $iambing_username = 'defaultuser';
    static private $iambing_password = 'defaultpass';

    static public function sync() {
        $httphelper = new HTTPHelper();

        echo "\n\nSYNCING Identities... \n";
        $graphene_response = $httphelper->http_fetch([
            'url'  => self::$iambing_url.'/api/public/identities/bulk_update',
            'verb' => 'POST',
            'data' => ['identities'=>self::$identities,'id'=>'bnumber'],
            'username' => self::$iambing_username,
            'password' => self::$iambing_password,
            'headers' => ['Accept'=>'application/json'],
        ]);
        var_dump($graphene_response['content']);    
    }

}

IAMBingBulkUpdateIdentities::sync();