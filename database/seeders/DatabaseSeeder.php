<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Configuration;
use App\Models\User;
use App\Models\Group;
use App\Models\System;
use App\Models\Entitlement;
use App\Models\GroupEntitlement;
use App\Models\Endpoint;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {

        $default_username_template = new Configuration(['name'=>'default_username_template','config'=>'{{first_name.0}}{{last_name.0}}{{last_name.1}}{{last_name.2}}{{last_name.3}}{{last_name.4}}{{last_name.5}}{{last_name.6}}{{last_name.7}}{{last_name.8}}{{last_name.9}}{{last_name.10}}{{#iterator}}{{iterator}}{{/iterator}}']);
        $default_username_template->save();
        $user_attributes = new Configuration(['name'=>'user_attributes','config'=>[['name'=>"nickname",'label'=>'Nickname'],['name'=>'email','label'=>'Default Email']]]);
        $user_attributes->save();
        $user_unique_ids = new Configuration(['name'=>'user_unique_ids','config'=>[['name'=>'bnumber','label'=>'BNumber']]]);
        $user_unique_ids->save();
        $affiliations = new Configuration(['name'=>'affiliations','config'=>['faculty','staff','student','employee','member','affiliate','alum','library-walk-in','applicant']]);
        $affiliations->save();
        
        $example_user = new User(['first_name'=>'Example','last_name'=>'User','attributes'=>['nickname'=>'Dude'],'default_username'=>'euser1']);
        $example_user->save();

        $group1 = new Group(['name'=>'Full Time Staff','user_id'=>$example_user->id,'affiliation'=>'staff','order'=>2]);
        $group1->save();
        $group2 = new Group(['name'=>'Part Time Staff','user_id'=>$example_user->id,'affiliation'=>'staff','order'=>4]);
        $group2->save();
        $group3 = new Group(['name'=>'Faculty','user_id'=>$example_user->id,'affiliation'=>'faculty','order'=>1]);
        $group3->save();
        $group4 = new Group(['name'=>'Student','user_id'=>$example_user->id,'affiliation'=>'student','order'=>3]);
        $group4->save();
        $group5 = new Group(['name'=>'Applicant','user_id'=>$example_user->id,'affiliation'=>'applicant','order'=>3]);
        $group5->save();

        $endpoint1 = new Endpoint(['name'=>'DataProxy Default','config'=>[
            'content_type' => 'application/x-www-form-urlencoded',
            'secret' => 'password',
            'type' => 'http_basic_auth',
            'url' => 'https://hermesprod.binghamton.edu/iam',
            'username' => 'username',
        ]]);
        $endpoint1->save();

        $system1 = new System(['name'=>'BU','config'=>['default_username_template'=>'{{default_username}}']]);
        $system1->save();
        $system2 = new System(['name'=>'Google Workspace','config'=>['default_username_template'=>'{{default_username}}@binghamton.edu']]);
        $system2->save();

        $entitlement1 = new Entitlement(['name'=>'Wifi','system_id'=>$system1->id]);
        $entitlement1->save();
        $entitlement2 = new Entitlement(['name'=>'VPN','system_id'=>$system1->id]);
        $entitlement2->save();
        $entitlement3 = new Entitlement(['name'=>'Email','system_id'=>$system2->id]);
        $entitlement3->save();
        $entitlement4 = new Entitlement(['name'=>'Google Drive','system_id'=>$system2->id]);
        $entitlement4->save();
        $entitlement5 = new Entitlement(['name'=>'Undergrad Slate','system_id'=>$system1->id]);
        $entitlement5->save();

        // Provision Wifi
        $group_entitlement1 = new GroupEntitlement(['group_id'=>$group1->id,'entitlement_id'=>$entitlement1->id]);
        $group_entitlement1->save();
        $group_entitlement2 = new GroupEntitlement(['group_id'=>$group2->id,'entitlement_id'=>$entitlement1->id]);
        $group_entitlement2->save();
        $group_entitlement3 = new GroupEntitlement(['group_id'=>$group3->id,'entitlement_id'=>$entitlement1->id]);
        $group_entitlement3->save();
        $group_entitlement4 = new GroupEntitlement(['group_id'=>$group4->id,'entitlement_id'=>$entitlement1->id]);
        $group_entitlement4->save();

        // Provision Email
        $group_entitlement5 = new GroupEntitlement(['group_id'=>$group1->id,'entitlement_id'=>$entitlement3->id]);
        $group_entitlement5->save();
        $group_entitlement6 = new GroupEntitlement(['group_id'=>$group2->id,'entitlement_id'=>$entitlement3->id]);
        $group_entitlement6->save();
        $group_entitlement7 = new GroupEntitlement(['group_id'=>$group3->id,'entitlement_id'=>$entitlement3->id]);
        $group_entitlement7->save();
        $group_entitlement8 = new GroupEntitlement(['group_id'=>$group4->id,'entitlement_id'=>$entitlement3->id]);
        $group_entitlement8->save();

        $group_entitlement9 = new GroupEntitlement(['group_id'=>$group1->id,'entitlement_id'=>$entitlement2->id]);
        $group_entitlement9->save();
        $group_entitlement10 = new GroupEntitlement(['group_id'=>$group3->id,'entitlement_id'=>$entitlement2->id]);
        $group_entitlement10->save();

        // Applicants in Slate
        $group_entitlement10 = new GroupEntitlement(['group_id'=>$group5->id,'entitlement_id'=>$entitlement5->id]);
        $group_entitlement10->save();

    }
}
