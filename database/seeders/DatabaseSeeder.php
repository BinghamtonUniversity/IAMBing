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

        $group1 = new Group(['name'=>'Staff','user_id'=>$example_user->id,'affiliation'=>'staff','order'=>2]);
        $group1->save();
        $group2 = new Group(['name'=>'Matriculated Students','user_id'=>$example_user->id,'affiliation'=>'student','order'=>3]);
        $group2->save();
        $group3 = new Group(['name'=>'Faculty','user_id'=>$example_user->id,'affiliation'=>'faculty','order'=>1]);
        $group3->save();
        $group4 = new Group(['name'=>'Nonatriculated Students','user_id'=>$example_user->id,'affiliation'=>'student','order'=>4]);
        $group4->save();
        $group5 = new Group(['name'=>'Applicants','user_id'=>$example_user->id,'affiliation'=>'applicant','order'=>5]);
        $group5->save();
        $group6 = new Group(['name'=>'Admitted Applicants','user_id'=>$example_user->id,'affiliation'=>'applicant','order'=>5]);
        $group6->save();
        $group7 = new Group(['name'=>'Alumni','user_id'=>$example_user->id,'affiliation'=>'alum','order'=>6]);
        $group7->save();
        $group8 = new Group(['name'=>'Recent Alumni','user_id'=>$example_user->id,'affiliation'=>'alum','order'=>6]);
        $group8->save();
        $group9 = new Group(['name'=>'Retirees','user_id'=>$example_user->id,'affiliation'=>null,'order'=>6]);
        $group9->save();
        $group10 = new Group(['name'=>'RF Staff','user_id'=>$example_user->id,'affiliation'=>'affiliate','order'=>6]);
        $group10->save();
        $group11 = new Group(['name'=>'Volunteers','user_id'=>$example_user->id,'affiliation'=>'affiliate','order'=>6]);
        $group11->save();


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
        $entitlement2 = new Entitlement(['name'=>'Staff VPN','system_id'=>$system1->id]);
        $entitlement2->save();
        $entitlement3 = new Entitlement(['name'=>'Email','system_id'=>$system2->id]);
        $entitlement3->save();
        $entitlement4 = new Entitlement(['name'=>'Google Drive','system_id'=>$system2->id]);
        $entitlement4->save();
        $entitlement5 = new Entitlement(['name'=>'Undergrad Slate','system_id'=>$system1->id]);
        $entitlement5->save();
        $entitlement6 = new Entitlement(['name'=>'Alumni Email','system_id'=>$system2->id]);
        $entitlement6->save();

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
        $group_entitlement8 = new GroupEntitlement(['group_id'=>$group10->id,'entitlement_id'=>$entitlement3->id]);
        $group_entitlement8->save();

        // Alumni Email
        $group_entitlement8 = new GroupEntitlement(['group_id'=>$group7->id,'entitlement_id'=>$entitlement6->id]);
        $group_entitlement8->save();
        $group_entitlement8 = new GroupEntitlement(['group_id'=>$group8->id,'entitlement_id'=>$entitlement6->id]);
        $group_entitlement8->save();

        // Staff VPN
        $group_entitlement9 = new GroupEntitlement(['group_id'=>$group1->id,'entitlement_id'=>$entitlement2->id]);
        $group_entitlement9->save();
        $group_entitlement10 = new GroupEntitlement(['group_id'=>$group3->id,'entitlement_id'=>$entitlement2->id]);
        $group_entitlement10->save();

        // Applicants in Slate
        $group_entitlement10 = new GroupEntitlement(['group_id'=>$group5->id,'entitlement_id'=>$entitlement5->id]);
        $group_entitlement10->save();
        $group_entitlement11 = new GroupEntitlement(['group_id'=>$group6->id,'entitlement_id'=>$entitlement5->id]);
        $group_entitlement11->save();

    }
}
