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
        $user_attributes = new Configuration(['name'=>'user_attributes','config'=>[['name'=>"nickname",'label'=>'Nickname'],['name'=>'binghamton_emails','label'=>'Binghamton Email','array'=>true],['name'=>'personal_emails','label'=>'Personal Email','array'=>true]]]);
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
        $group4 = new Group(['name'=>'Nonmatriculated Students','user_id'=>$example_user->id,'affiliation'=>'student','order'=>4]);
        $group4->save();
        $group5 = new Group(['name'=>'Applicants','user_id'=>$example_user->id,'affiliation'=>'applicant','order'=>5]);
        $group5->save();
        $group6 = new Group(['name'=>'Admitted Applicants','user_id'=>$example_user->id,'affiliation'=>'applicant','order'=>5]);
        $group6->save();
        $group7 = new Group(['name'=>'Alumni','user_id'=>$example_user->id,'affiliation'=>'alum','order'=>6]);
        $group7->save();
        $group8 = new Group(['name'=>'Recent Alumni','user_id'=>$example_user->id,'affiliation'=>'alum','order'=>6]);
        $group8->save();
        // $group9 = new Group(['name'=>'Retirees','user_id'=>$example_user->id,'affiliation'=>null,'order'=>6]);
        // $group9->save();
        $group10 = new Group(['name'=>'RF Staff','user_id'=>$example_user->id,'affiliation'=>'affiliate','order'=>6]);
        $group10->save();
        $group11 = new Group(['name'=>'Volunteers','user_id'=>$example_user->id,'affiliation'=>'affiliate','order'=>6]);
        $group11->save();
        // $group12 = new Group(['name'=>'Sponsored Accounts in BU','user_id'=>$example_user->id,'order'=>6]);
        // $group12->save();
        // $group13 = new Group(['name'=>'Sponsored Accounts in Google','user_id'=>$example_user->id,'order'=>6]);
        // $group13->save();
        // $group14 = new Group(['name'=>'Bulk Loaded Users','user_id'=>$example_user->id,'order'=>6]);
        // $group14->save();
        
        //START 11/09/2021, AKT - Added the groups below
        $group15 = new Group(['name'=>'On Campus Students','user_id'=>$example_user->id,'order'=>6]);
        $group15->save();
        $group16 = new Group(['name'=>'Secondary Accounts','user_id'=>$example_user->id,'type'=>'manual','order'=>6]);
        $group16->save();
        $group17 = new Group(['name'=>'Vendors','user_id'=>$example_user->id,'type'=>'manual','order'=>6]);
        $group17->save();
        $group18 = new Group(['name'=>'AdminNetAccess VPN','user_id'=>$example_user->id,'type'=>'manual','order'=>6]);
        $group18->save();
        $group19 = new Group(['name'=>'AdminNetAccess Wifi','user_id'=>$example_user->id,'type'=>'manual','order'=>6]);
        $group19->save();
        $group20 = new Group(['name'=>'Deny Wifi','user_id'=>$example_user->id,'type'=>'manual','order'=>6]);
        $group20->save();
        $group21 = new Group(['name'=>'Other Bingview','user_id'=>$example_user->id,'type'=>'manual','order'=>6]);
        $group21->save();
        $group22 = new Group(['name'=>'RDP Access','user_id'=>$example_user->id,'type'=>'manual','order'=>6]);
        $group22->save();
        $group23 = new Group(['name'=>'Interactive Login','user_id'=>$example_user->id,'type'=>'manual','order'=>6]);
        $group23->save();
        $group24 = new Group(['name'=>'Email Address','user_id'=>$example_user->id,'type'=>'manual','order'=>6]);
        $group24->save();
        $group25 = new Group(['name'=>'Network Shares','user_id'=>$example_user->id,'type'=>'manual','order'=>6]);
        $group25->save();
        $group26 = new Group(['name'=>'Spectrum Access','user_id'=>$example_user->id,'type'=>'manual','order'=>6]);
        $group26->save();
        //END 11/09/2021, AKT

        $endpoint1 = new Endpoint(['name'=>'DataProxy Default','config'=>[
            'content_type' => 'application/x-www-form-urlencoded',
            'secret' => '',
            'type' => 'http_basic_auth',
            'url' => 'https://hermesdev.binghamton.edu/iam',
            'username' => '',
        ]]);
        $endpoint1->save();

        $system1 = new System(['name'=>'BU','default_account_id_template'=>'{{default_username}}','onremove' => 'disable','config'=>[
            'actions' => [
              [
                'path' => '/ad/user/{{account.account_id}}',
                'verb' => 'PUT',
                'action' => 'create',
                'endpoint' => '1',
                'response_code' => 200,
              ],
              [
                'path' => '/ad/user/{{account.account_id}}',
                'verb' => 'PUT',
                'action' => 'update',
                'endpoint' => '1',
                'response_code' => 200,
              ],
              [
                'path' => '/ad/user/{{account.account_id}}',
                'verb' => 'DELETE',
                'action' => 'delete',
                'endpoint' => '1',
                'response_code' => 200,
              ],
              [
                'path' => '/ad/user/{{account.account_id}}',
                'verb' => 'GET',
                'action' => 'info',
                'endpoint' => '1',
                'response_code' => 200,
              ]
            ]
          ]]);
        $system1->save();
        $system2 = new System(['name'=>'Google Workspace','default_account_id_template'=>'{{default_username}}@binghamton.edu','onremove' => 'delete','config'=>[]]);
        $system2->save();

        $entitlement1 = new Entitlement(['name'=>'Employee Wifi','system_id'=>$system1->id]);
        $entitlement1->save();
        $entitlement2 = new Entitlement(['name'=>'Employee VPN','system_id'=>$system1->id]);
        $entitlement2->save();
        $entitlement3 = new Entitlement(['name'=>'Staff Bingview','system_id'=>$system1->id]);
        $entitlement3->save();
        $entitlement4 = new Entitlement(['name'=>'Google Account','system_id'=>$system2->id]);
        $entitlement4->save();
        $entitlement5 = new Entitlement(['name'=>'Undergrad Slate','system_id'=>$system1->id]);
        $entitlement5->save();
        $entitlement6 = new Entitlement(['name'=>'Enforce 2FA','system_id'=>$system1->id]);
        $entitlement6->save();
        $entitlement7 = new Entitlement(['name'=>'Student Wifi','system_id'=>$system1->id]);
        $entitlement7->save();
        $entitlement8 = new Entitlement(['name'=>'BU Account','system_id'=>$system1->id]);
        $entitlement8->save();
        
        //START 11/09/2021, AKT - Added the entitlements below
        $entitlement9 = new Entitlement(['name'=>'AdminNetAccess Wifi','system_id'=>$system1->id]);
        $entitlement9->save();
        $entitlement10 = new Entitlement(['name'=>'Deny Wifi','system_id'=>$system1->id]);
        $entitlement10->save();
        $entitlement11 = new Entitlement(['name'=>'Student VPN','system_id'=>$system1->id]);
        $entitlement11->save();
        $entitlement12 = new Entitlement(['name'=>'Student Bingview','system_id'=>$system1->id]);
        $entitlement12->save();
        $entitlement13 = new Entitlement(['name'=>'Faculty Bingview','system_id'=>$system1->id]);
        $entitlement13->save();
        $entitlement14 = new Entitlement(['name'=>'Interactive Login','system_id'=>$system1->id]);
        $entitlement14->save();
        $entitlement15 = new Entitlement(['name'=>'RDP Access','system_id'=>$system1->id]);
        $entitlement15->save();
        $entitlement16 = new Entitlement(['name'=>'AdminNetAccess VPN','system_id'=>$system1->id]);
        $entitlement16->save();
        $entitlement17 = new Entitlement(['name'=>'Other Bingview','system_id'=>$system1->id]);
        $entitlement17->save();
        $entitlement18 = new Entitlement(['name'=>'Spectrum Access','system_id'=>$system1->id]);
        $entitlement18->save();

        //Google System Operations
        $entitlement19 = new Entitlement(['name'=>'Google Email','system_id'=>$system2->id]);
        $entitlement19->save();
        $entitlement20 = new Entitlement(['name'=>'Google Drive','system_id'=>$system2->id]);
        $entitlement20->save();
        $entitlement21 = new Entitlement(['name'=>'Google Sites','system_id'=>$system2->id]);
        $entitlement21->save();
        $entitlement22 = new Entitlement(['name'=>'Google Chat','system_id'=>$system2->id]);
        $entitlement22->save();
        $entitlement23 = new Entitlement(['name'=>'Google Groups','system_id'=>$system2->id]);
        $entitlement23->save();
        $entitlement24 = new Entitlement(['name'=>'Google Calendar','system_id'=>$system2->id]);
        $entitlement24->save();
        $entitlement25 = new Entitlement(['name'=>'Google Classroom','system_id'=>$system2->id]);
        $entitlement25->save();
        $entitlement26 = new Entitlement(['name'=>'Google Vault','system_id'=>$system2->id]);
        $entitlement26->save();
        //END 11/09/2021, AKT

        // Provision Wifi //
        //START 11/16/2021, AKT - Added new group entitlements below, and added more explanations in the comments
        // Employee Wifi
        $group_entitlement1 = new GroupEntitlement(['group_id'=>$group1->id,'entitlement_id'=>$entitlement1->id]);
        $group_entitlement1->save();
        $group_entitlement3 = new GroupEntitlement(['group_id'=>$group3->id,'entitlement_id'=>$entitlement1->id]);
        $group_entitlement3->save();
        $group_entitlement26 = new GroupEntitlement(['group_id'=>$group10->id,'entitlement_id'=>$entitlement1->id]);
        $group_entitlement26->save();
        $group_entitlement27 = new GroupEntitlement(['group_id'=>$group11->id,'entitlement_id'=>$entitlement1->id]);
        $group_entitlement27->save();
        // Student Wifi
        $group_entitlement2 = new GroupEntitlement(['group_id'=>$group2->id,'entitlement_id'=>$entitlement7->id]);
        $group_entitlement2->save();
        $group_entitlement4 = new GroupEntitlement(['group_id'=>$group4->id,'entitlement_id'=>$entitlement7->id]);
        $group_entitlement4->save();
        $group_entitlement28 = new GroupEntitlement(['group_id'=>$group8->id,'entitlement_id'=>$entitlement7->id]);
        $group_entitlement28->save();
        //AdminNetAccess Wifi
        $group_entitlement29 = new GroupEntitlement(['group_id'=>$group19->id,'entitlement_id'=>$entitlement9->id]);
        $group_entitlement29->save();
        //Deny Wifi
        $group_entitlement30 = new GroupEntitlement(['group_id'=>$group20->id,'entitlement_id'=>$entitlement10->id]);
        $group_entitlement30->save();

        // Provision Google //
        
        //Google Account
        $group_entitlement5 = new GroupEntitlement(['group_id'=>$group1->id,'entitlement_id'=>$entitlement4->id]);
        $group_entitlement5->save();
        $group_entitlement6 = new GroupEntitlement(['group_id'=>$group2->id,'entitlement_id'=>$entitlement4->id]);
        $group_entitlement6->save();
        $group_entitlement7 = new GroupEntitlement(['group_id'=>$group3->id,'entitlement_id'=>$entitlement4->id]);
        $group_entitlement7->save();
        $group_entitlement8 = new GroupEntitlement(['group_id'=>$group4->id,'entitlement_id'=>$entitlement4->id]);
        $group_entitlement8->save();
        $group_entitlement8 = new GroupEntitlement(['group_id'=>$group10->id,'entitlement_id'=>$entitlement4->id]);
        $group_entitlement8->save();
        // $group_entitlement31 = new GroupEntitlement(['group_id'=>$group7->id,'entitlement_id'=>$entitlement4->id]);
        // $group_entitlement31->save();
        $group_entitlement32 = new GroupEntitlement(['group_id'=>$group8->id,'entitlement_id'=>$entitlement4->id]);
        $group_entitlement32->save();
        // $group_entitlement33 = new GroupEntitlement(['group_id'=>$group11->id,'entitlement_id'=>$entitlement4->id]);
        // $group_entitlement33->save();
        $group_entitlement34 = new GroupEntitlement(['group_id'=>$group24->id,'entitlement_id'=>$entitlement4->id]);
        $group_entitlement34->save();

        //Google Email
        $group_entitlement53 = new GroupEntitlement(['group_id'=>$group1->id,'entitlement_id'=>$entitlement19->id]);
        $group_entitlement53->save();
        $group_entitlement54 = new GroupEntitlement(['group_id'=>$group2->id,'entitlement_id'=>$entitlement19->id]);
        $group_entitlement54->save();
        $group_entitlement55 = new GroupEntitlement(['group_id'=>$group3->id,'entitlement_id'=>$entitlement19->id]);
        $group_entitlement55->save();
        $group_entitlement56 = new GroupEntitlement(['group_id'=>$group4->id,'entitlement_id'=>$entitlement19->id]);
        $group_entitlement56->save();
        $group_entitlement57 = new GroupEntitlement(['group_id'=>$group10->id,'entitlement_id'=>$entitlement19->id]);
        $group_entitlement57->save();
        // $group_entitlement58 = new GroupEntitlement(['group_id'=>$group7->id,'entitlement_id'=>$entitlement4->id]);
        // $group_entitlement58->save();
        $group_entitlement59 = new GroupEntitlement(['group_id'=>$group8->id,'entitlement_id'=>$entitlement19->id]);
        $group_entitlement59->save();
        // $group_entitlement60 = new GroupEntitlement(['group_id'=>$group11->id,'entitlement_id'=>$entitlement4->id]);
        // $group_entitlement60->save();
        $group_entitlement61 = new GroupEntitlement(['group_id'=>$group24->id,'entitlement_id'=>$entitlement19->id]);
        $group_entitlement61->save();

        // Google Drive
        $group_entitlement62 = new GroupEntitlement(['group_id'=>$group1->id,'entitlement_id'=>$entitlement20->id]);
        $group_entitlement62->save();
        $group_entitlement63 = new GroupEntitlement(['group_id'=>$group2->id,'entitlement_id'=>$entitlement20->id]);
        $group_entitlement63->save();
        $group_entitlement64 = new GroupEntitlement(['group_id'=>$group3->id,'entitlement_id'=>$entitlement20->id]);
        $group_entitlement64->save();
        $group_entitlement65 = new GroupEntitlement(['group_id'=>$group4->id,'entitlement_id'=>$entitlement20->id]);
        $group_entitlement65->save();
        $group_entitlement68 = new GroupEntitlement(['group_id'=>$group8->id,'entitlement_id'=>$entitlement20->id]);
        $group_entitlement68->save();
        $group_entitlement66 = new GroupEntitlement(['group_id'=>$group10->id,'entitlement_id'=>$entitlement20->id]);
        $group_entitlement66->save();
        
        // Google Sites
        $group_entitlement67 = new GroupEntitlement(['group_id'=>$group1->id,'entitlement_id'=>$entitlement21->id]);
        $group_entitlement67->save();
        // $group_entitlement68 = new GroupEntitlement(['group_id'=>$group2->id,'entitlement_id'=>$entitlement21->id]);
        // $group_entitlement68->save();
        $group_entitlement69 = new GroupEntitlement(['group_id'=>$group3->id,'entitlement_id'=>$entitlement21->id]);
        $group_entitlement69->save();
        // $group_entitlement70 = new GroupEntitlement(['group_id'=>$group4->id,'entitlement_id'=>$entitlement21->id]);
        // $group_entitlement70->save();
        $group_entitlement71 = new GroupEntitlement(['group_id'=>$group8->id,'entitlement_id'=>$entitlement21->id]);
        $group_entitlement71->save();
        $group_entitlement72 = new GroupEntitlement(['group_id'=>$group10->id,'entitlement_id'=>$entitlement21->id]);
        $group_entitlement72->save();

        // Google Chat
        $group_entitlement73 = new GroupEntitlement(['group_id'=>$group1->id,'entitlement_id'=>$entitlement22->id]);
        $group_entitlement73->save();
        $group_entitlement74 = new GroupEntitlement(['group_id'=>$group2->id,'entitlement_id'=>$entitlement22->id]);
        $group_entitlement74->save();
        $group_entitlement75 = new GroupEntitlement(['group_id'=>$group3->id,'entitlement_id'=>$entitlement22->id]);
        $group_entitlement75->save();
        $group_entitlement76 = new GroupEntitlement(['group_id'=>$group4->id,'entitlement_id'=>$entitlement22->id]);
        $group_entitlement76->save();
        $group_entitlement77 = new GroupEntitlement(['group_id'=>$group8->id,'entitlement_id'=>$entitlement22->id]);
        $group_entitlement77->save();
        $group_entitlement78 = new GroupEntitlement(['group_id'=>$group10->id,'entitlement_id'=>$entitlement22->id]);
        $group_entitlement78->save();

        // Google Groups
        $group_entitlement79 = new GroupEntitlement(['group_id'=>$group1->id,'entitlement_id'=>$entitlement23->id]);
        $group_entitlement79->save();
        // $group_entitlement80 = new GroupEntitlement(['group_id'=>$group2->id,'entitlement_id'=>$entitlement23->id]);
        // $group_entitlement80->save();
        $group_entitlement81 = new GroupEntitlement(['group_id'=>$group3->id,'entitlement_id'=>$entitlement23->id]);
        $group_entitlement81->save();
        // $group_entitlement82 = new GroupEntitlement(['group_id'=>$group4->id,'entitlement_id'=>$entitlement23->id]);
        // $group_entitlement82->save();
        $group_entitlement83 = new GroupEntitlement(['group_id'=>$group8->id,'entitlement_id'=>$entitlement23->id]);
        $group_entitlement83->save();
        $group_entitlement84 = new GroupEntitlement(['group_id'=>$group10->id,'entitlement_id'=>$entitlement23->id]);
        $group_entitlement84->save();

        // Google Calendar
        $group_entitlement85 = new GroupEntitlement(['group_id'=>$group1->id,'entitlement_id'=>$entitlement24->id]);
        $group_entitlement85->save();
        $group_entitlement86 = new GroupEntitlement(['group_id'=>$group2->id,'entitlement_id'=>$entitlement24->id]);
        $group_entitlement86->save();
        $group_entitlement87 = new GroupEntitlement(['group_id'=>$group3->id,'entitlement_id'=>$entitlement24->id]);
        $group_entitlement87->save();
        $group_entitlement88 = new GroupEntitlement(['group_id'=>$group4->id,'entitlement_id'=>$entitlement24->id]);
        $group_entitlement88->save();
        $group_entitlement89 = new GroupEntitlement(['group_id'=>$group8->id,'entitlement_id'=>$entitlement24->id]);
        $group_entitlement89->save();
        $group_entitlement90 = new GroupEntitlement(['group_id'=>$group10->id,'entitlement_id'=>$entitlement24->id]);
        $group_entitlement90->save();

        // Google Classroom
        $group_entitlement91 = new GroupEntitlement(['group_id'=>$group1->id,'entitlement_id'=>$entitlement25->id]);
        $group_entitlement91->save();
        $group_entitlement92 = new GroupEntitlement(['group_id'=>$group2->id,'entitlement_id'=>$entitlement25->id]);
        $group_entitlement92->save();
        $group_entitlement93 = new GroupEntitlement(['group_id'=>$group3->id,'entitlement_id'=>$entitlement25->id]);
        $group_entitlement93->save();
        $group_entitlement94 = new GroupEntitlement(['group_id'=>$group4->id,'entitlement_id'=>$entitlement25->id]);
        $group_entitlement94->save();
        $group_entitlement95 = new GroupEntitlement(['group_id'=>$group8->id,'entitlement_id'=>$entitlement25->id]);
        $group_entitlement95->save();
        $group_entitlement96 = new GroupEntitlement(['group_id'=>$group10->id,'entitlement_id'=>$entitlement25->id]);
        $group_entitlement96->save();

        // Google Vault
        $group_entitlement97 = new GroupEntitlement(['group_id'=>$group1->id,'entitlement_id'=>$entitlement26->id]);
        $group_entitlement97->save();
        $group_entitlement98 = new GroupEntitlement(['group_id'=>$group2->id,'entitlement_id'=>$entitlement26->id]);
        $group_entitlement98->save();
        $group_entitlement99 = new GroupEntitlement(['group_id'=>$group3->id,'entitlement_id'=>$entitlement26->id]);
        $group_entitlement99->save();
        $group_entitlement100 = new GroupEntitlement(['group_id'=>$group4->id,'entitlement_id'=>$entitlement26->id]);
        $group_entitlement100->save();
        $group_entitlement101= new GroupEntitlement(['group_id'=>$group8->id,'entitlement_id'=>$entitlement26->id]);
        $group_entitlement101->save();
        $group_entitlement102 = new GroupEntitlement(['group_id'=>$group10->id,'entitlement_id'=>$entitlement26->id]);
        $group_entitlement102->save();

        // Provision VPN //
        // Employee VPN
        $group_entitlement9 = new GroupEntitlement(['group_id'=>$group1->id,'entitlement_id'=>$entitlement2->id]);
        $group_entitlement9->save();
        $group_entitlement10 = new GroupEntitlement(['group_id'=>$group3->id,'entitlement_id'=>$entitlement2->id]);
        $group_entitlement10->save();
        $group_entitlement35 = new GroupEntitlement(['group_id'=>$group10->id,'entitlement_id'=>$entitlement2->id]);
        $group_entitlement35->save();
        $group_entitlement36 = new GroupEntitlement(['group_id'=>$group11->id,'entitlement_id'=>$entitlement2->id]);
        $group_entitlement36->save();

        // Student VPN
        $group_entitlement37 = new GroupEntitlement(['group_id'=>$group2->id,'entitlement_id'=>$entitlement11->id]);
        $group_entitlement37->save();
        $group_entitlement38 = new GroupEntitlement(['group_id'=>$group4->id,'entitlement_id'=>$entitlement11->id]);
        $group_entitlement38->save();
        $group_entitlement39 = new GroupEntitlement(['group_id'=>$group8->id,'entitlement_id'=>$entitlement11->id]);
        $group_entitlement39->save();

        // AdminNetAccess VPN
        $group_entitlement40 = new GroupEntitlement(['group_id'=>$group18->id,'entitlement_id'=>$entitlement16->id]);
        $group_entitlement40->save();
        //END Provision VPN

        // Applicants in Slate
        $group_entitlement10 = new GroupEntitlement(['group_id'=>$group5->id,'entitlement_id'=>$entitlement5->id]);
        $group_entitlement10->save();
        $group_entitlement11 = new GroupEntitlement(['group_id'=>$group6->id,'entitlement_id'=>$entitlement5->id]);
        $group_entitlement11->save();

        // Enforce 2FA
        $group_entitlement12 = new GroupEntitlement(['group_id'=>$group1->id,'entitlement_id'=>$entitlement6->id]);
        $group_entitlement12->save();
        $group_entitlement13 = new GroupEntitlement(['group_id'=>$group2->id,'entitlement_id'=>$entitlement6->id]);
        $group_entitlement13->save();
        $group_entitlement14 = new GroupEntitlement(['group_id'=>$group3->id,'entitlement_id'=>$entitlement6->id]);
        $group_entitlement14->save();
        $group_entitlement15 = new GroupEntitlement(['group_id'=>$group4->id,'entitlement_id'=>$entitlement6->id]);
        $group_entitlement15->save();
        $group_entitlement16 = new GroupEntitlement(['group_id'=>$group8->id,'entitlement_id'=>$entitlement6->id]);
        $group_entitlement16->save();
        // $group_entitlement17 = new GroupEntitlement(['group_id'=>$group9->id,'entitlement_id'=>$entitlement6->id]);
        // $group_entitlement17->save();
        $group_entitlement18 = new GroupEntitlement(['group_id'=>$group10->id,'entitlement_id'=>$entitlement6->id]);
        $group_entitlement18->save();
        $group_entitlement19 = new GroupEntitlement(['group_id'=>$group11->id,'entitlement_id'=>$entitlement6->id]);
        $group_entitlement19->save();

        // Provision Bingview //
        // Staff Bingview
        $group_entitlement20 = new GroupEntitlement(['group_id'=>$group1->id,'entitlement_id'=>$entitlement3->id]);
        $group_entitlement20->save();
        $group_entitlement21 = new GroupEntitlement(['group_id'=>$group21->id,'entitlement_id'=>$entitlement3->id]);
        $group_entitlement21->save();

        // Student Bingview
        $group_entitlement41 = new GroupEntitlement(['group_id'=>$group2->id,'entitlement_id'=>$entitlement12->id]);
        $group_entitlement41->save();
        $group_entitlement42 = new GroupEntitlement(['group_id'=>$group4->id,'entitlement_id'=>$entitlement12->id]);
        $group_entitlement42->save();

        //Faculty Bingview
        $group_entitlement43 = new GroupEntitlement(['group_id'=>$group3->id,'entitlement_id'=>$entitlement13->id]);
        $group_entitlement43->save();

        //Other Bingview
        $group_entitlement44 = new GroupEntitlement(['group_id'=>$group21->id,'entitlement_id'=>$entitlement17->id]);
        $group_entitlement44->save();
        //END Provision Bingview //

        // RDP Access //
        $group_entitlement45 = new GroupEntitlement(['group_id'=>$group22->id,'entitlement_id'=>$entitlement15->id]);
        $group_entitlement45->save();

        // Interactive Login //
        $group_entitlement45 = new GroupEntitlement(['group_id'=>$group23->id,'entitlement_id'=>$entitlement14->id]);
        $group_entitlement45->save();
        $group_entitlement46 = new GroupEntitlement(['group_id'=>$group1->id,'entitlement_id'=>$entitlement14->id]);
        $group_entitlement46->save();
        $group_entitlement47 = new GroupEntitlement(['group_id'=>$group2->id,'entitlement_id'=>$entitlement14->id]);
        $group_entitlement47->save();
        $group_entitlement48 = new GroupEntitlement(['group_id'=>$group3->id,'entitlement_id'=>$entitlement14->id]);
        $group_entitlement48->save();
        $group_entitlement49 = new GroupEntitlement(['group_id'=>$group4->id,'entitlement_id'=>$entitlement14->id]);
        $group_entitlement49->save();
        $group_entitlement50 = new GroupEntitlement(['group_id'=>$group10->id,'entitlement_id'=>$entitlement14->id]);
        $group_entitlement50->save();

        // Spectrum Access // 
        $group_entitlement51 = new GroupEntitlement(['group_id'=>$group15->id,'entitlement_id'=>$entitlement18->id]);
        $group_entitlement51->save();
        $group_entitlement52 = new GroupEntitlement(['group_id'=>$group26->id,'entitlement_id'=>$entitlement18->id]);
        $group_entitlement52->save();


        // BU Only Accounts
        // $group_entitlement23 = new GroupEntitlement(['group_id'=>$group12->id,'entitlement_id'=>$entitlement8->id]);
        // $group_entitlement23->save();
        // $group_entitlement25 = new GroupEntitlement(['group_id'=>$group14->id,'entitlement_id'=>$entitlement8->id]);
        // $group_entitlement25->save();
    }
}
