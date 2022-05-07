<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Configuration;
use App\Models\Identity;
use App\Models\Group;
use App\Models\System;
use App\Models\Entitlement;
use App\Models\GroupEntitlement;
use App\Models\Endpoint;
use App\Models\Permission;
use App\Models\IdentityUniqueID;

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
        $identity_attributes = new Configuration(['name'=>'identity_attributes','config'=>[]]);
        $identity_attributes->save();
        $identity_unique_ids = new Configuration(['name'=>'identity_unique_ids','config'=>[['name'=>'bnumber','label'=>'BNumber'],['name'=>'suny_id','label'=>'SUNY ID'],['name'=>'millennium_id','label'=>'Millennium ID']]]);
        $identity_unique_ids->save();
        $affiliations = new Configuration(['name'=>'affiliations','config'=>['faculty','staff','student','employee','member','affiliate','alum','library-walk-in','applicant']]);
        $affiliations->save();
        $username_availability = new Configuration(['name'=>'username_availability',
        'config'=>[
          'path' => '/users/username_info',
          'verb' => 'GET',
          'endpoint' => '1',
          'available_response' => 404,
          'not_available_response'=>200
          ]]);
        $username_availability->save();

        // Create Example Identities
        $example_identity = new Identity(['first_name'=>'Example','last_name'=>'Identity','attributes'=>[],'default_username'=>'eidentity1']);
        $example_identity->save();
        $example_identity2 = new Identity(['first_name'=>'Ali Kemal','last_name'=>'Tanriverdi','attributes'=>[],
                                  'default_username'=>'atanrive','default_email'=>'atanrive@binghamton.edu'
                                ]);
        $example_identity2->save();
        $example_identity3 = new Identity(['first_name'=>'Tim','last_name'=>'Cortesi','attributes'=>[],
                                  'default_username'=>'tcortesi','default_email'=>'tcortesi@binghamton.edu'
                                ]);
        $example_identity3->save();

        // Create unique id for the identity and give the sample identities the manage_identity_permissions for initial tests
        $example_unique_id = new IdentityUniqueID(['identity_id'=>2,'name'=>"bnumber",'value'=>"B00450942"]);
        $example_unique_id->save();
        $example_unique_id2 = new IdentityUniqueID(['identity_id'=>3,'name'=>"bnumber",'value'=>"B00505893"]);
        $example_unique_id2->save();

        $permission = new Permission(['identity_id'=>2,'permission'=>"manage_identity_permissions"]);
        $permission->save();
        $permission2 = new Permission(['identity_id'=>3,'permission'=>"manage_identity_permissions"]);
        $permission2->save();
        
  
        // Group creations
        $staff = new Group(['slug'=>'staff','name'=>"Staff",'affiliation'=>'staff','order'=>2]);
        $staff->save();
        $students = new Group(['slug'=>'students','name'=>"Students",'affiliation'=>'student','order'=>3]);
        $students->save();
        $faculty = new Group(['slug'=>'faculty',"name"=>"Faculty",'affiliation'=>'faculty','order'=>1]);
        $faculty->save();
        // $nonstudents = new Group(['slug'=>'nonmatriculated_students','name'=>'Nonmatriculated Students','affiliation'=>'student','order'=>4]);
        // $nonmatriculated_students->save();
        $applicants = new Group(['slug'=>'applicants','name'=>"Applicants",'affiliation'=>'applicant','order'=>5]);
        $applicants->save();
        $admitted_applicants = new Group(['slug'=>'admitted_applicants','name'=>"Admitted Applicants",'affiliation'=>'applicant','order'=>5]);
        $admitted_applicants->save();
        $alumni_affiliates = new Group(['slug'=>'alumni_affiliates','name'=>"Alumni Affiliates",'affiliation'=>'applicant','order'=>5]);
        $alumni_affiliates->save();
        $alumni = new Group(['slug'=>'alumni','name'=>"Alumni",'affiliation'=>'alum','order'=>6]);
        $alumni->save();
        $recent_alumni = new Group(['slug'=>'recent_alumni','name'=>"Recent Alumni",'affiliation'=>'alum','order'=>6]);
        $recent_alumni->save();
        $retirees = new Group(['slug'=>'retirees','name'=>'Retirees','affiliation'=>null,'order'=>6]);
        $retirees->save();
        $rf_staff = new Group(['slug'=>'rf_staff','name'=>'RF Staff','affiliation'=>'affiliate','order'=>6]);
        $rf_staff->save();
        $volunteers = new Group(['slug'=>'volunteers','name'=>"Volunteers",'affiliation'=>'affiliate','order'=>6]);
        $volunteers->save();
        $on_campus_students = new Group(['slug'=>'on_campus_students','name'=>'On Campus Students','order'=>6]);
        $on_campus_students->save();
        $secondary_accounts = new Group(['slug'=>'secondary_accounts','name'=>"Secondary Accounts",'type'=>'manual','order'=>6]);
        $secondary_accounts->save();
        $vendors = new Group(['slug'=>'vendors','name'=>"Vendors",'type'=>'manual','order'=>6]);
        $vendors->save();
        $admin_net_access_vpn = new Group(['slug'=>'admin_net_access_vpn',"name"=>"Admin Net Access VPN",'type'=>'manual','order'=>6]);
        $admin_net_access_vpn->save();
        $admin_net_access_wifi = new Group(['slug'=>'admin_net_access_wifi','name'=>"Admin Net Access Wifi",'type'=>'manual','order'=>6]);
        $admin_net_access_wifi->save();
        $deny_wifi = new Group(['slug'=>'deny_wifi','name'=>"Deny Wifi",'type'=>'manual','order'=>6]);
        $deny_wifi->save();
        $other_bingview = new Group(['slug'=>'other_bingview','name'=>"Other Bingview",'type'=>'manual','order'=>6]);
        $other_bingview->save();
        $rdp_access = new Group(['slug'=>'rdp_access','name'=>"RDP Access",'type'=>'manual','order'=>6]);
        $rdp_access->save();
        $interactive_login = new Group(['slug'=>'interactive_login','name'=>"Interactive Login",'type'=>'manual','order'=>6]);
        $interactive_login->save();
        $email_address = new Group(['slug'=>'email_address','name'=>"Email Address",'type'=>'manual','order'=>6]);
        $email_address->save();
        $network_shares = new Group(['slug'=>'network_shares','name'=>"Network Shares",'type'=>'manual','order'=>6]);
        $network_shares->save();
        $spectrum_access = new Group(['slug'=>'spectrum_access','name'=>"Spectrum Acess",'type'=>'manual','order'=>6]);
        $spectrum_access->save();
        $recent_faculty = new Group(['slug'=>'recent_faculty','name'=>"Recent Faculty",'order'=>6]);
        $recent_faculty->save();
        $online_students = new Group(['slug'=>'online_students','name'=>"Online Students",'order'=>6]);
        $online_students->save();
        $currently_enrolled_students = new Group(['slug'=>'currently_enrolled_students','name'=>"Currently Enrolled Students",'order'=>6]);
        $currently_enrolled_students->save();
        $alumnni_email = new Group(['slug'=>'alumni_email','name'=>"Alumni Email",'order'=>6]);
        $alumnni_email->save();
        // END Group Creations

        // Create sample endpoint
        $endpoint1 = new Endpoint(['name'=>'DataProxy Default','config'=>[
            'content_type' => 'application/x-www-form-urlencoded',
            'secret' => '',
            'type' => 'http_basic_auth',
            'url' => 'https://hermesdev.binghamton.edu/iam',
            'username' => '',
        ]]);
        $endpoint1->save();
        
        // Create sample systems
        $system1 = new System(['name'=>'BU','default_account_id_template'=>'{{default_username}}','onremove' => 'delete','config'=>[
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
        $system2 = new System(['name'=>'Google Workspace',
                              'default_account_id_template'=>'{{default_username}}@binghamton.edu',
                              'onremove' => 'delete',
                              'config'=>[
                                  'actions'=>[
                                      [
                                          'path' => '/google/user/{{account.account_id}}@binghamton.edu',
                                          'verb' => 'GET',
                                          'action' => 'info',
                                          'endpoint' => '1',
                                          'response_code' => 200,
                                      ],
                                      [
                                          'path' => '/google/user/{{account.account_id}}@binghamton.edu',
                                          'verb' => 'PUT',
                                          'action' => 'update',
                                          'endpoint' => '1',
                                          'response_code' => 200,
                                      ],
                                      [
                                          'path' => '/google/user/{{account.account_id}}@binghamton.edu',
                                          'verb' => 'PUT',
                                          'action' => 'create',
                                          'endpoint' => '1',
                                          'response_code' => 200,
                                      ],
                                  ]
                              ]
                            ]);
        $system2->save();
        $system3 = new System(['name'=>'Banner',
          'default_account_id_template'=>'{{ids.bnumber}}',
          'onremove' => 'delete',
          'config'=>[
            'actions'=>[
                [
                'path' => '/banner/goremal/{{default_username}}',
                'verb' => 'GET',
                'action' => 'info',
                'endpoint' => '1',
                'response_code' => 200,
                ],
                [
                    'path' => '/banner/goremal',
                    'verb' => 'POST',
                    'action' => 'create',
                    'endpoint' => '1',
                    'response_code' => 200,
                ],
                [
                    'path' => '/banner/goremal',
                    'verb' => 'PUT',
                    'action' => 'update',
                    'endpoint' => '1',
                    'response_code' => 200,
                ]
            ]
          ]
        ]);
        $system3->save();

        // BU/ AD Entitlements
        $bu_account_ent = new Entitlement(['name'=>'BU Account','system_id'=>$system1->id]);
        $bu_account_ent->save();
        $employee_wifi_ent = new Entitlement(['name'=>'Employee Wifi','system_id'=>$system1->id]);
        $employee_wifi_ent->save();
        $employee_vpn_ent = new Entitlement(['name'=>'Employee VPN','system_id'=>$system1->id]);
        $employee_vpn_ent->save();
        $staff_bingview_ent = new Entitlement(['name'=>'Staff Bingview','system_id'=>$system1->id]);
        $staff_bingview_ent->save();
        $enforce_2fa_ent = new Entitlement(['name'=>'Enforce 2FA','system_id'=>$system1->id]);
        $enforce_2fa_ent->save();
        $student_wifi_ent = new Entitlement(['name'=>'Student Wifi','system_id'=>$system1->id]);
        $student_wifi_ent->save();
        $admin_net_access_wifi_ent = new Entitlement(['name'=>'AdminNetAccess Wifi','system_id'=>$system1->id]);
        $admin_net_access_wifi_ent->save();
        $deny_wifi_ent = new Entitlement(['name'=>'Deny Wifi','system_id'=>$system1->id]);
        $deny_wifi_ent->save();
        $student_vpn_ent = new Entitlement(['name'=>'Student VPN','system_id'=>$system1->id]);
        $student_vpn_ent->save();
        $student_bingview_ent = new Entitlement(['name'=>'Student Bingview','system_id'=>$system1->id]);
        $student_bingview_ent->save();
        $faculty_bingview_ent = new Entitlement(['name'=>'Faculty Bingview','system_id'=>$system1->id]);
        $faculty_bingview_ent->save();
        $interactive_login_ent = new Entitlement(['name'=>'Interactive Login','system_id'=>$system1->id]);
        $interactive_login_ent->save();
        $rdp_access_ent = new Entitlement(['name'=>'RDP Access','system_id'=>$system1->id]);
        $rdp_access_ent->save();
        $admin_net_access_vpn_ent = new Entitlement(['name'=>'AdminNetAccess VPN','system_id'=>$system1->id]);
        $admin_net_access_vpn_ent->save();
        $other_bingview_ent = new Entitlement(['name'=>'Other Bingview','system_id'=>$system1->id]);
        $other_bingview_ent->save();
        $spectrum_access_ent = new Entitlement(['name'=>'Spectrum Access','system_id'=>$system1->id]);
        $spectrum_access_ent->save();

        //Google System Entitlements
        $google_account_google_ent = new Entitlement(['name'=>'Google Account','system_id'=>$system2->id]);
        $google_account_google_ent->save();
        $google_email_google_ent = new Entitlement(['name'=>'Google Email','system_id'=>$system2->id]);
        $google_email_google_ent->save();
        $google_drive_google_ent = new Entitlement(['name'=>'Google Drive','system_id'=>$system2->id]);
        $google_drive_google_ent->save();
        $google_sites_google_ent = new Entitlement(['name'=>'Google Sites','system_id'=>$system2->id]);
        $google_sites_google_ent->save();
        $google_chat_google_ent = new Entitlement(['name'=>'Google Chat','system_id'=>$system2->id]);
        $google_chat_google_ent->save();
        $google_groups_google_ent = new Entitlement(['name'=>'Google Groups','system_id'=>$system2->id]);
        $google_groups_google_ent->save();
        $google_calendar_google_ent = new Entitlement(['name'=>'Google Calendar','system_id'=>$system2->id]);
        $google_calendar_google_ent->save();
        $google_classroom_google_ent = new Entitlement(['name'=>'Google Classroom','system_id'=>$system2->id]);
        $google_classroom_google_ent->save();
        $google_vault_google_ent = new Entitlement(['name'=>'Google Vault','system_id'=>$system2->id]);
        $google_vault_google_ent->save();
        $google_addons_google_ent = new Entitlement(['name'=>'Google Addons','system_id'=>$system2->id]);
        $google_addons_google_ent->save();

        // Banner System Entitlements
        $banner_student_entitlement = new Entitlement(['name'=>'Banner Student','system_id'=>$system3->id]);
        $banner_student_entitlement->save();
        $banner_employee_entitlement = new Entitlement(['name'=>'Banner Employee','system_id'=>$system3->id]);
        $banner_employee_entitlement->save();

      //START BU/ AD Account Entitlements Creations

        //BU Account Group Entitlements Definitions
        $bu_account_group_ent1 = new GroupEntitlement(['group_id'=>$faculty->id,'entitlement_id'=>$bu_account_ent->id]);
        $bu_account_group_ent1->save();
        $bu_account_group_ent2 = new GroupEntitlement(['group_id'=>$recent_faculty->id,'entitlement_id'=>$bu_account_ent->id]);
        $bu_account_group_ent2->save();
        $bu_account_group_ent3 = new GroupEntitlement(['group_id'=>$staff->id,'entitlement_id'=>$bu_account_ent->id]);
        $bu_account_group_ent3->save();
        $bu_account_group_ent4 = new GroupEntitlement(['group_id'=>$students->id,'entitlement_id'=>$bu_account_ent->id]);
        $bu_account_group_ent4->save();
        $bu_account_group_ent5 = new GroupEntitlement(['group_id'=>$online_students->id,'entitlement_id'=>$bu_account_ent->id]);
        $bu_account_group_ent5->save();
        // $bu_account_group_ent6 = new GroupEntitlement(['group_id'=>$nonmatriculated_students->id,'entitlement_id'=>$bu_account_ent->id]);
        // $bu_account_group_ent6->save();
        $bu_account_group_ent7 = new GroupEntitlement(['group_id'=>$currently_enrolled_students->id,'entitlement_id'=>$bu_account_ent->id]);
        $bu_account_group_ent7->save();
        $bu_account_group_ent8 = new GroupEntitlement(['group_id'=>$on_campus_students->id,'entitlement_id'=>$bu_account_ent->id]);
        $bu_account_group_ent8->save();
        $bu_account_group_ent9 = new GroupEntitlement(['group_id'=>$applicants->id,'entitlement_id'=>$bu_account_ent->id]);
        $bu_account_group_ent9->save();
        $bu_account_group_ent10 = new GroupEntitlement(['group_id'=>$admitted_applicants->id,'entitlement_id'=>$bu_account_ent->id]);
        $bu_account_group_ent10->save();
        $bu_account_group_ent11 = new GroupEntitlement(['group_id'=>$alumni->id,'entitlement_id'=>$bu_account_ent->id]);
        $bu_account_group_ent11->save();
        $bu_account_group_ent12 = new GroupEntitlement(['group_id'=>$recent_alumni->id,'entitlement_id'=>$bu_account_ent->id]);
        $bu_account_group_ent12->save();
        $bu_account_group_ent13 = new GroupEntitlement(['group_id'=>$rf_staff->id,'entitlement_id'=>$bu_account_ent->id]);
        $bu_account_group_ent13->save();
        $bu_account_group_ent14 = new GroupEntitlement(['group_id'=>$admin_net_access_vpn->id,'entitlement_id'=>$bu_account_ent->id]);
        $bu_account_group_ent14->save();
        $bu_account_group_ent15 = new GroupEntitlement(['group_id'=>$admin_net_access_wifi->id,'entitlement_id'=>$bu_account_ent->id]);
        $bu_account_group_ent15->save();
        $bu_account_group_ent16 = new GroupEntitlement(['group_id'=>$deny_wifi->id,'entitlement_id'=>$bu_account_ent->id]);
        $bu_account_group_ent16->save();
        $bu_account_group_ent17 = new GroupEntitlement(['group_id'=>$other_bingview->id,'entitlement_id'=>$bu_account_ent->id]);
        $bu_account_group_ent17->save();
        $bu_account_group_ent18 = new GroupEntitlement(['group_id'=>$rdp_access->id,'entitlement_id'=>$bu_account_ent->id]);
        $bu_account_group_ent18->save();
        $bu_account_group_ent19 = new GroupEntitlement(['group_id'=>$interactive_login->id,'entitlement_id'=>$bu_account_ent->id]);
        $bu_account_group_ent19->save();
        $bu_account_group_ent20 = new GroupEntitlement(['group_id'=>$email_address->id,'entitlement_id'=>$bu_account_ent->id]);
        $bu_account_group_ent20->save();
        $bu_account_group_ent21 = new GroupEntitlement(['group_id'=>$network_shares->id,'entitlement_id'=>$bu_account_ent->id]);
        $bu_account_group_ent21->save();
        $bu_account_group_ent21 = new GroupEntitlement(['group_id'=>$spectrum_access->id,'entitlement_id'=>$bu_account_ent->id]);
        $bu_account_group_ent21->save();

        //Encforce 2FA Group Entitlements Definitions
        $enforce_2fa_group_ent1=  new GroupEntitlement(['group_id'=>$faculty->id,'entitlement_id'=>$enforce_2fa_ent->id]);
        $enforce_2fa_group_ent1->save();
        $enforce_2fa_group_ent2=  new GroupEntitlement(['group_id'=>$staff->id,'entitlement_id'=>$enforce_2fa_ent->id]);
        $enforce_2fa_group_ent2->save();
        $enforce_2fa_group_ent3=  new GroupEntitlement(['group_id'=>$students->id,'entitlement_id'=>$enforce_2fa_ent->id]);
        $enforce_2fa_group_ent3->save();
        $enforce_2fa_group_ent4=  new GroupEntitlement(['group_id'=>$online_students->id,'entitlement_id'=>$enforce_2fa_ent->id]);
        $enforce_2fa_group_ent4->save();
        // $enforce_2fa_group_ent5=  new GroupEntitlement(['group_id'=>$nonmatriculated_students->id,'entitlement_id'=>$enforce_2fa_ent->id]);
        // $enforce_2fa_group_ent5->save();
        $enforce_2fa_group_ent6=  new GroupEntitlement(['group_id'=>$currently_enrolled_students->id,'entitlement_id'=>$enforce_2fa_ent->id]);
        $enforce_2fa_group_ent6->save();
        $enforce_2fa_group_ent7=  new GroupEntitlement(['group_id'=>$recent_alumni->id,'entitlement_id'=>$enforce_2fa_ent->id]);
        $enforce_2fa_group_ent7->save();
        $enforce_2fa_group_ent8=  new GroupEntitlement(['group_id'=>$rf_staff->id,'entitlement_id'=>$enforce_2fa_ent->id]);
        $enforce_2fa_group_ent8->save();
        $enforce_2fa_group_ent9=  new GroupEntitlement(['group_id'=>$admin_net_access_vpn->id,'entitlement_id'=>$enforce_2fa_ent->id]);
        $enforce_2fa_group_ent9->save();
        $enforce_2fa_group_ent10=  new GroupEntitlement(['group_id'=>$admin_net_access_wifi->id,'entitlement_id'=>$enforce_2fa_ent->id]);
        $enforce_2fa_group_ent10->save();
        $enforce_2fa_group_ent11=  new GroupEntitlement(['group_id'=>$deny_wifi->id,'entitlement_id'=>$enforce_2fa_ent->id]);
        $enforce_2fa_group_ent11->save();
        $enforce_2fa_group_ent12=  new GroupEntitlement(['group_id'=>$other_bingview->id,'entitlement_id'=>$enforce_2fa_ent->id]);
        $enforce_2fa_group_ent12->save();
        $enforce_2fa_group_ent13=  new GroupEntitlement(['group_id'=>$rdp_access->id,'entitlement_id'=>$enforce_2fa_ent->id]);
        $enforce_2fa_group_ent13->save();
        $enforce_2fa_group_ent14=  new GroupEntitlement(['group_id'=>$interactive_login->id,'entitlement_id'=>$enforce_2fa_ent->id]);
        $enforce_2fa_group_ent14->save();
        $enforce_2fa_group_ent15=  new GroupEntitlement(['group_id'=>$network_shares->id,'entitlement_id'=>$enforce_2fa_ent->id]);
        $enforce_2fa_group_ent15->save();
        $enforce_2fa_group_ent16=  new GroupEntitlement(['group_id'=>$spectrum_access->id,'entitlement_id'=>$enforce_2fa_ent->id]);
        $enforce_2fa_group_ent16->save();
        
        // Student Wifi
        $student_wifi_group_ent1 = new GroupEntitlement(['group_id'=>$students->id,'entitlement_id'=>$student_wifi_ent->id]);
        $student_wifi_group_ent1->save();
        $student_wifi_group_ent2 = new GroupEntitlement(['group_id'=>$online_students->id,'entitlement_id'=>$student_wifi_ent->id]);
        $student_wifi_group_ent2->save();
        $student_wifi_group_ent3 = new GroupEntitlement(['group_id'=>$currently_enrolled_students->id,'entitlement_id'=>$student_wifi_ent->id]);
        $student_wifi_group_ent3->save();
        $student_wifi_group_ent4 = new GroupEntitlement(['group_id'=>$recent_alumni->id,'entitlement_id'=>$student_wifi_ent->id]);
        $student_wifi_group_ent4->save();
                            
        // Employee Wifi
        $employee_wifi_group_ent1 = new GroupEntitlement(['group_id'=>$staff->id,'entitlement_id'=>$employee_wifi_ent->id]);
        $employee_wifi_group_ent1->save();
        $employee_wifi_group_ent2 = new GroupEntitlement(['group_id'=>$faculty->id,'entitlement_id'=>$employee_wifi_ent->id]);
        $employee_wifi_group_ent2->save();
        $employee_wifi_group_ent3 = new GroupEntitlement(['group_id'=>$rf_staff->id,'entitlement_id'=>$employee_wifi_ent->id]);
        $employee_wifi_group_ent3->save();

        
        // AdminNetAccess Wifi
        $admin_net_access_wifi_group_ent1 = new GroupEntitlement(['group_id'=>$admin_net_access_wifi->id,'entitlement_id'=>$admin_net_access_wifi_ent->id]);
        $admin_net_access_wifi_group_ent1->save();

        // Deny Wifi
        $deny_wifi_group_ent1 = new GroupEntitlement(['group_id'=>$deny_wifi->id,'entitlement_id'=>$deny_wifi_ent->id]);
        $deny_wifi_group_ent1->save();

        // Student VPN
        $student_vpn_group_ent1 = new GroupEntitlement(['group_id'=>$students->id,'entitlement_id'=>$student_vpn_ent->id]);
        $student_vpn_group_ent1->save();
        $student_vpn_group_ent2 = new GroupEntitlement(['group_id'=>$online_students->id,'entitlement_id'=>$student_vpn_ent->id]);
        $student_vpn_group_ent2->save();
        $student_vpn_group_ent3 = new GroupEntitlement(['group_id'=>$currently_enrolled_students->id,'entitlement_id'=>$student_vpn_ent->id]);
        $student_vpn_group_ent3->save();
        $student_vpn_group_ent4 = new GroupEntitlement(['group_id'=>$recent_alumni->id,'entitlement_id'=>$student_vpn_ent->id]);
        $student_vpn_group_ent4->save();

        // Employee VPN
        $employee_vpn_group_ent1 = new GroupEntitlement(['group_id'=>$staff->id,'entitlement_id'=>$employee_vpn_ent->id]);
        $employee_vpn_group_ent1->save();
        $employee_vpn_group_ent2 = new GroupEntitlement(['group_id'=>$faculty->id,'entitlement_id'=>$employee_vpn_ent->id]);
        $employee_vpn_group_ent2->save();
        $employee_vpn_group_ent3 = new GroupEntitlement(['group_id'=>$rf_staff->id,'entitlement_id'=>$employee_vpn_ent->id]);
        $employee_vpn_group_ent3->save();

        // AdminNetAccess VPN
        $admin_net_access_vpn_group_ent1 = new GroupEntitlement(['group_id'=>$admin_net_access_wifi->id,'entitlement_id'=>$admin_net_access_vpn_ent->id]);
        $admin_net_access_vpn_group_ent1->save();

        // Student Bingview
        $student_bingview_group_ent1 = new GroupEntitlement(['group_id'=>$students->id,'entitlement_id'=>$student_bingview_ent->id]);
        $student_bingview_group_ent1->save();
        $student_bingview_group_ent2 = new GroupEntitlement(['group_id'=>$online_students->id,'entitlement_id'=>$student_bingview_ent->id]);
        $student_bingview_group_ent2->save();
        $student_bingview_group_ent3 = new GroupEntitlement(['group_id'=>$currently_enrolled_students->id,'entitlement_id'=>$student_bingview_ent->id]);
        $student_bingview_group_ent3->save();

        // Faculty Bingview
        $faculty_bingview_group_ent1 = new GroupEntitlement(['group_id'=>$faculty->id,'entitlement_id'=>$faculty_bingview_ent->id]);
        $faculty_bingview_group_ent1->save();
        
        // Faculty Bingview
        $staff_bingview_group_ent1 = new GroupEntitlement(['group_id'=>$staff->id,'entitlement_id'=>$staff_bingview_ent->id]);
        $staff_bingview_group_ent1->save();
        $staff_bingview_group_ent2 = new GroupEntitlement(['group_id'=>$other_bingview->id,'entitlement_id'=>$staff_bingview_ent->id]);
        $staff_bingview_group_ent2->save();

        // Other Bingview
        $other_bingview_group_ent1 = new GroupEntitlement(['group_id'=>$other_bingview->id,'entitlement_id'=>$other_bingview_ent->id]);
        $other_bingview_group_ent1->save();

        // RDP Access
        $rdp_access_group_ent1 = new GroupEntitlement(['group_id'=>$rdp_access->id,'entitlement_id'=>$rdp_access_ent->id]);
        $rdp_access_group_ent1->save();

        // Interactive Login
        $interactive_login_group_ent1 = new GroupEntitlement(['group_id'=>$faculty->id,'entitlement_id'=>$interactive_login_ent->id]);
        $interactive_login_group_ent1->save();
        $interactive_login_group_ent2 = new GroupEntitlement(['group_id'=>$staff->id,'entitlement_id'=>$interactive_login_ent->id]);
        $interactive_login_group_ent2->save();
        $interactive_login_group_ent3 = new GroupEntitlement(['group_id'=>$students->id,'entitlement_id'=>$interactive_login_ent->id]);
        $interactive_login_group_ent3->save();
        $interactive_login_group_ent4 = new GroupEntitlement(['group_id'=>$online_students->id,'entitlement_id'=>$interactive_login_ent->id]);
        $interactive_login_group_ent4->save();
        $interactive_login_group_ent5 = new GroupEntitlement(['group_id'=>$currently_enrolled_students->id,'entitlement_id'=>$interactive_login_ent->id]);
        $interactive_login_group_ent5->save();
        $interactive_login_group_ent6 = new GroupEntitlement(['group_id'=>$rf_staff->id,'entitlement_id'=>$interactive_login_ent->id]);
        $interactive_login_group_ent6->save();
        $interactive_login_group_ent7 = new GroupEntitlement(['group_id'=>$interactive_login->id,'entitlement_id'=>$interactive_login_ent->id]);
        $interactive_login_group_ent7->save();

        // Spectrum Access
        $spectrum_access_group_ent1 = new GroupEntitlement(['group_id'=>$on_campus_students->id,'entitlement_id'=>$spectrum_access_ent->id]);
        $spectrum_access_group_ent1->save();
        $spectrum_access_group_ent2 = new GroupEntitlement(['group_id'=>$spectrum_access->id,'entitlement_id'=>$spectrum_access_ent->id]);
        $spectrum_access_group_ent2->save();

        // END BU/ AD Account Entitlements Creations // 

        // START Google Group Entitlements//

        //Google Account
        $google_account_group_google_ent1 = new GroupEntitlement(['group_id'=>$faculty->id,'entitlement_id'=>$google_account_google_ent->id]);
        $google_account_group_google_ent1->save();
        $google_account_group_google_ent2 = new GroupEntitlement(['group_id'=>$recent_faculty->id,'entitlement_id'=>$google_account_google_ent->id]);
        $google_account_group_google_ent2->save();
        $google_account_group_google_ent3 = new GroupEntitlement(['group_id'=>$staff->id,'entitlement_id'=>$google_account_google_ent->id]);
        $google_account_group_google_ent3->save();
        $google_account_group_google_ent4 = new GroupEntitlement(['group_id'=>$students->id,'entitlement_id'=>$google_account_google_ent->id]);
        $google_account_group_google_ent4->save();
        $google_account_group_google_ent5 = new GroupEntitlement(['group_id'=>$online_students->id,'entitlement_id'=>$google_account_google_ent->id]);
        $google_account_group_google_ent5->save();
        // $google_account_group_google_ent6 = new GroupEntitlement(['group_id'=>$nonmatriculated_students->id,'entitlement_id'=>$google_account_google_ent->id]);
        // $google_account_group_google_ent6->save();
        $google_account_group_google_ent7 = new GroupEntitlement(['group_id'=>$currently_enrolled_students->id,'entitlement_id'=>$google_account_google_ent->id]);
        $google_account_group_google_ent7->save();
        $google_account_group_google_ent8 = new GroupEntitlement(['group_id'=>$recent_alumni->id,'entitlement_id'=>$google_account_google_ent->id]);
        $google_account_group_google_ent8->save();
        $google_account_group_google_ent9 = new GroupEntitlement(['group_id'=>$rf_staff->id,'entitlement_id'=>$google_account_google_ent->id]);
        $google_account_group_google_ent9->save();
        $google_account_group_google_ent10 = new GroupEntitlement(['group_id'=>$email_address->id,'entitlement_id'=>$google_account_google_ent->id]);
        $google_account_group_google_ent10->save();
        
        // Google Email
        $google_email_group_google_ent1 = new GroupEntitlement(['group_id'=>$faculty->id,'entitlement_id'=>$google_email_google_ent->id]);
        $google_email_group_google_ent1->save();
        $google_email_group_google_ent2 = new GroupEntitlement(['group_id'=>$staff->id,'entitlement_id'=>$google_email_google_ent->id]);
        $google_email_group_google_ent2->save();
        $google_email_group_google_ent3 = new GroupEntitlement(['group_id'=>$students->id,'entitlement_id'=>$google_email_google_ent->id]);
        $google_email_group_google_ent3->save();
        $google_email_group_google_ent4 = new GroupEntitlement(['group_id'=>$online_students->id,'entitlement_id'=>$google_email_google_ent->id]);
        $google_email_group_google_ent4->save();
        // $google_email_group_google_ent5 = new GroupEntitlement(['group_id'=>$nonmatriculated_students->id,'entitlement_id'=>$google_email_google_ent->id]);
        // $google_email_group_google_ent5->save();
        $google_email_group_google_ent6 = new GroupEntitlement(['group_id'=>$currently_enrolled_students->id,'entitlement_id'=>$google_email_google_ent->id]);
        $google_email_group_google_ent6->save();
        $google_email_group_google_ent7 = new GroupEntitlement(['group_id'=>$recent_alumni->id,'entitlement_id'=>$google_email_google_ent->id]);
        $google_email_group_google_ent7->save();
        $google_email_group_google_ent8 = new GroupEntitlement(['group_id'=>$rf_staff->id,'entitlement_id'=>$google_email_google_ent->id]);
        $google_email_group_google_ent8->save();
        $google_email_group_google_ent9 = new GroupEntitlement(['group_id'=>$email_address->id,'entitlement_id'=>$google_email_google_ent->id]);
        $google_email_group_google_ent9->save();
        $google_email_group_google_ent10 = new GroupEntitlement(['group_id'=>$alumnni_email->id,'entitlement_id'=>$google_email_google_ent->id]);
        $google_email_group_google_ent10->save();

        // Google Drive
        $google_drive_group_google_ent1 = new GroupEntitlement(['group_id'=>$faculty->id,'entitlement_id'=>$google_drive_google_ent->id]);
        $google_drive_group_google_ent1->save();
        $google_drive_group_google_ent2 = new GroupEntitlement(['group_id'=>$staff->id,'entitlement_id'=>$google_drive_google_ent->id]);
        $google_drive_group_google_ent2->save();
        $google_drive_group_google_ent3 = new GroupEntitlement(['group_id'=>$students->id,'entitlement_id'=>$google_drive_google_ent->id]);
        $google_drive_group_google_ent3->save();
        $google_drive_group_google_ent4 = new GroupEntitlement(['group_id'=>$online_students->id,'entitlement_id'=>$google_drive_google_ent->id]);
        $google_drive_group_google_ent4->save();
        $google_drive_group_google_ent5 = new GroupEntitlement(['group_id'=>$currently_enrolled_students->id,'entitlement_id'=>$google_drive_google_ent->id]);
        $google_drive_group_google_ent5->save();
        $google_drive_group_google_ent6 = new GroupEntitlement(['group_id'=>$recent_alumni->id,'entitlement_id'=>$google_drive_google_ent->id]);
        $google_drive_group_google_ent6->save();
        $google_drive_group_google_ent7 = new GroupEntitlement(['group_id'=>$rf_staff->id,'entitlement_id'=>$google_drive_google_ent->id]);
        $google_drive_group_google_ent7->save();

        // Google Sites

        $google_sites_group_google_ent1 = new GroupEntitlement(['group_id'=>$faculty->id,'entitlement_id'=>$google_sites_google_ent->id]);
        $google_sites_group_google_ent1->save();
        $google_sites_group_google_ent2 = new GroupEntitlement(['group_id'=>$staff->id,'entitlement_id'=>$google_sites_google_ent->id]);
        $google_sites_group_google_ent2->save();
        $google_sites_group_google_ent3 = new GroupEntitlement(['group_id'=>$rf_staff->id,'entitlement_id'=>$google_sites_google_ent->id]);
        $google_sites_group_google_ent3->save();


        // Google Chat
        $google_chat_group_google_ent1 = new GroupEntitlement(['group_id'=>$faculty->id,'entitlement_id'=>$google_chat_google_ent->id]);
        $google_chat_group_google_ent1->save();
        $google_chat_group_google_ent2 = new GroupEntitlement(['group_id'=>$staff->id,'entitlement_id'=>$google_chat_google_ent->id]);
        $google_chat_group_google_ent2->save();
        $google_chat_group_google_ent3 = new GroupEntitlement(['group_id'=>$students->id,'entitlement_id'=>$google_chat_google_ent->id]);
        $google_chat_group_google_ent3->save();
        $google_chat_group_google_ent4 = new GroupEntitlement(['group_id'=>$online_students->id,'entitlement_id'=>$google_chat_google_ent->id]);
        $google_chat_group_google_ent4->save();
        $google_chat_group_google_ent5 = new GroupEntitlement(['group_id'=>$currently_enrolled_students->id,'entitlement_id'=>$google_chat_google_ent->id]);
        $google_chat_group_google_ent5->save();
        $google_chat_group_google_ent6 = new GroupEntitlement(['group_id'=>$recent_alumni->id,'entitlement_id'=>$google_chat_google_ent->id]);
        $google_chat_group_google_ent6->save();
        $google_chat_group_google_ent7 = new GroupEntitlement(['group_id'=>$rf_staff->id,'entitlement_id'=>$google_chat_google_ent->id]);
        $google_chat_group_google_ent7->save();

        // Google Groups
        $google_groups_group_google_ent1 = new GroupEntitlement(['group_id'=>$faculty->id,'entitlement_id'=>$google_groups_google_ent->id]);
        $google_groups_group_google_ent1->save();
        $google_groups_group_google_ent2 = new GroupEntitlement(['group_id'=>$staff->id,'entitlement_id'=>$google_groups_google_ent->id]);
        $google_groups_group_google_ent2->save();
        $google_groups_group_google_ent3 = new GroupEntitlement(['group_id'=>$students->id,'entitlement_id'=>$google_groups_google_ent->id]);
        $google_groups_group_google_ent3->save();
        $google_groups_group_google_ent4 = new GroupEntitlement(['group_id'=>$online_students->id,'entitlement_id'=>$google_groups_google_ent->id]);
        $google_groups_group_google_ent4->save();
        $google_groups_group_google_ent5 = new GroupEntitlement(['group_id'=>$currently_enrolled_students->id,'entitlement_id'=>$google_groups_google_ent->id]);
        $google_groups_group_google_ent5->save();
        $google_groups_group_google_ent6 = new GroupEntitlement(['group_id'=>$recent_alumni->id,'entitlement_id'=>$google_groups_google_ent->id]);
        $google_groups_group_google_ent6->save();
        $google_groups_group_google_ent7 = new GroupEntitlement(['group_id'=>$rf_staff->id,'entitlement_id'=>$google_groups_google_ent->id]);
        $google_groups_group_google_ent7->save();

        // Google Calendar
        $google_calendar_group_google_ent1 = new GroupEntitlement(['group_id'=>$faculty->id,'entitlement_id'=>$google_calendar_google_ent->id]);
        $google_calendar_group_google_ent1->save();
        $google_calendar_group_google_ent2 = new GroupEntitlement(['group_id'=>$staff->id,'entitlement_id'=>$google_calendar_google_ent->id]);
        $google_calendar_group_google_ent2->save();
        $google_calendar_group_google_ent3 = new GroupEntitlement(['group_id'=>$students->id,'entitlement_id'=>$google_calendar_google_ent->id]);
        $google_calendar_group_google_ent3->save();
        $google_calendar_group_google_ent4 = new GroupEntitlement(['group_id'=>$online_students->id,'entitlement_id'=>$google_calendar_google_ent->id]);
        $google_calendar_group_google_ent4->save();
        $google_calendar_group_google_ent5 = new GroupEntitlement(['group_id'=>$currently_enrolled_students->id,'entitlement_id'=>$google_calendar_google_ent->id]);
        $google_calendar_group_google_ent5->save();
        $google_calendar_group_google_ent6 = new GroupEntitlement(['group_id'=>$recent_alumni->id,'entitlement_id'=>$google_calendar_google_ent->id]);
        $google_calendar_group_google_ent6->save();
        $google_calendar_group_google_ent7 = new GroupEntitlement(['group_id'=>$rf_staff->id,'entitlement_id'=>$google_calendar_google_ent->id]);
        $google_calendar_group_google_ent7->save();

        // Google Classroom
        $google_classroom_group_google_ent1 = new GroupEntitlement(['group_id'=>$faculty->id,'entitlement_id'=>$google_classroom_google_ent->id]);
        $google_classroom_group_google_ent1->save();
        $google_classroom_group_google_ent2 = new GroupEntitlement(['group_id'=>$staff->id,'entitlement_id'=>$google_classroom_google_ent->id]);
        $google_classroom_group_google_ent2->save();
        $google_classroom_group_google_ent3 = new GroupEntitlement(['group_id'=>$students->id,'entitlement_id'=>$google_classroom_google_ent->id]);
        $google_classroom_group_google_ent3->save();
        $google_classroom_group_google_ent4 = new GroupEntitlement(['group_id'=>$online_students->id,'entitlement_id'=>$google_classroom_google_ent->id]);
        $google_classroom_group_google_ent4->save();
        $google_classroom_group_google_ent5 = new GroupEntitlement(['group_id'=>$currently_enrolled_students->id,'entitlement_id'=>$google_classroom_google_ent->id]);
        $google_classroom_group_google_ent5->save();
        $google_classroom_group_google_ent6 = new GroupEntitlement(['group_id'=>$recent_alumni->id,'entitlement_id'=>$google_classroom_google_ent->id]);
        $google_classroom_group_google_ent6->save();
        $google_classroom_group_google_ent7 = new GroupEntitlement(['group_id'=>$rf_staff->id,'entitlement_id'=>$google_classroom_google_ent->id]);
        $google_classroom_group_google_ent7->save();

        // Google Vault
        /* No ONE */

        // Google Addons
        $google_addons_group_google_ent1 = new GroupEntitlement(['group_id'=>$faculty->id,'entitlement_id'=>$google_addons_google_ent->id]);
        $google_addons_group_google_ent1->save();
        $google_addons_group_google_ent2 = new GroupEntitlement(['group_id'=>$staff->id,'entitlement_id'=>$google_addons_google_ent->id]);
        $google_addons_group_google_ent2->save();
        $google_addons_group_google_ent3 = new GroupEntitlement(['group_id'=>$students->id,'entitlement_id'=>$google_addons_google_ent->id]);
        $google_addons_group_google_ent3->save();
        $google_addons_group_google_ent4 = new GroupEntitlement(['group_id'=>$online_students->id,'entitlement_id'=>$google_addons_google_ent->id]);
        $google_addons_group_google_ent4->save();
        $google_addons_group_google_ent5 = new GroupEntitlement(['group_id'=>$currently_enrolled_students->id,'entitlement_id'=>$google_addons_google_ent->id]);
        $google_addons_group_google_ent5->save();
        $google_addons_group_google_ent6 = new GroupEntitlement(['group_id'=>$recent_alumni->id,'entitlement_id'=>$google_addons_google_ent->id]);
        $google_addons_group_google_ent6->save();
        $google_addons_group_google_ent7 = new GroupEntitlement(['group_id'=>$rf_staff->id,'entitlement_id'=>$google_addons_google_ent->id]);
        $google_addons_group_google_ent7->save();

        // END Google Group Entitlements//

        // Banner Entitlements //

        // Banner Student Entitlements
        $banner_students_ent1 = new GroupEntitlement(['group_id'=>$students->id,'entitlement_id'=>$banner_student_entitlement->id]);
        $banner_students_ent1->save();
        $banner_students_ent2 = new GroupEntitlement(['group_id'=>$online_students->id,'entitlement_id'=>$banner_student_entitlement->id]);
        $banner_students_ent2->save();
        $banner_students_ent3 = new GroupEntitlement(['group_id'=>$currently_enrolled_students->id,'entitlement_id'=>$banner_student_entitlement->id]);
        $banner_students_ent3->save();
        $banner_students_ent4 = new GroupEntitlement(['group_id'=>$on_campus_students->id,'entitlement_id'=>$banner_student_entitlement->id]);
        $banner_students_ent4->save();
        $banner_students_ent5 = new GroupEntitlement(['group_id'=>$admitted_applicants->id,'entitlement_id'=>$banner_student_entitlement->id]);
        $banner_students_ent5->save();
        $banner_students_ent6 = new GroupEntitlement(['group_id'=>$alumni_affiliates->id,'entitlement_id'=>$banner_student_entitlement->id]);
        $banner_students_ent6->save();
        $banner_students_ent7 = new GroupEntitlement(['group_id'=>$alumni->id,'entitlement_id'=>$banner_student_entitlement->id]);
        $banner_students_ent7->save();
        $banner_students_ent8 = new GroupEntitlement(['group_id'=>$recent_alumni->id,'entitlement_id'=>$banner_student_entitlement->id]);
        $banner_students_ent8->save();

        //Banner Employee Entitlements
        $banner_employee_ent1 = new GroupEntitlement(['group_id'=>$staff->id,'entitlement_id'=>$banner_employee_entitlement->id]);
        $banner_employee_ent1->save();
        $banner_employee_ent2 = new GroupEntitlement(['group_id'=>$faculty->id,'entitlement_id'=>$banner_employee_entitlement->id]);
        $banner_employee_ent2->save();
        $banner_employee_ent3 = new GroupEntitlement(['group_id'=>$recent_faculty->id,'entitlement_id'=>$banner_employee_entitlement->id]);
        $banner_employee_ent3->save();
        $banner_employee_ent4 = new GroupEntitlement(['group_id'=>$rf_staff->id,'entitlement_id'=>$banner_employee_entitlement->id]);
        $banner_employee_ent4->save();
        $banner_employee_ent5 = new GroupEntitlement(['group_id'=>$retirees->id,'entitlement_id'=>$banner_employee_entitlement->id]);
        $banner_employee_ent5->save();

    }
}
