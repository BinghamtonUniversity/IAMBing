<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\User;
use App\Models\System;
use App\Models\Account;
use App\Models\UserUniqueID;
use App\Models\GroupMember;
use App\Models\Group;

class InitBulkLoad extends Command
{
    protected $name = 'initbulkload';
    protected $description = 'Load the Initial Bulk Load File from Accounts Management / AD';

    private function default_username($current_default, $new_username) {
        if (!is_null($current_default)) {
            // Establish Default Username
            if (is_numeric(substr($current_default, -1, 1)) && !is_numeric(substr($new_username, -1, 1))) {
                $current_default = $new_username;
            } else if (is_numeric(substr($current_default, -1, 1)) && is_numeric(substr($new_username, -1, 1))) {
                if (substr($new_username, -1, 1) < substr($current_default, -1, 1)) {
                    $current_default = $new_username;
                }
            }
        } else {
            $current_default = $new_username;
        }
        return $current_default;
    }

    public function handle() {
        ini_set('memory_limit','1024M');
        if ($this->confirm('Are you sure you want to load this data?  This action can not be undone')) {
            $filename = $this->ask('What is the filename/filepath for the default JSON Bulk Load?');

            $this->info("Attempting to load the data...");
            $data_raw = file_get_contents($filename);
            $data = json_decode($data_raw,true);

            $bu_sys = System::where('name','BU')->first();
            $google_sys = System::where('name','Google Workspace')->first();
            $bulk_loaded_users_group = Group::where('name','Bulk Loaded Users')->first();

            // Create All PRIMARY Account Users and Accounts
            foreach($data['users'] as $user) {
                $new_user = new User([
                    'first_name'=>$user['firstName'],
                    'last_name'=>$user['lastName'],
                    'ids' => ['bnumber'=>$user['uniqueId']],
                ]);
                $new_user->save();  
                GroupMember::updateOrCreate(['group_id'=>$bulk_loaded_users_group->id,'user_id'=>$new_user->id],[]);                            
                $default_username = null;
                $default_email = null;
                foreach($user['accounts'] as $account) {
                    if ($account['affiliation'] === 'Primary Account' || $account['affiliation'] === 'Non-Active Account') {
                        if ($account['system'] === 'PODS') {
                            if ($account['affiliation'] === 'Primary Account') {
                                $new_account = new Account([
                                    'user_id'=>$new_user->id,
                                    'system_id'=>$bu_sys->id,
                                    'account_id'=>strtolower($account['username'])
                                ]);
                                $new_account->save();
                            }
                            $default_username = $this->default_username($default_username, $account['username']);
                        } else if ($account['system'] === 'Google') {
                            if ($account['affiliation'] === 'Primary Account') {
                                $new_account = new Account([
                                    'user_id'=>$new_user->id,
                                    'system_id'=>$google_sys->id,
                                    'account_id'=>strtolower($account['email'])
                                ]);
                                $new_account->save();
                            }
                            $default_email = $this->default_username($default_email,explode('@',$account['email'])[0]);
                        }
                    }
                } 
                $new_user->default_username = strtolower($default_username);
                $new_user->default_email = strtolower($default_email).'@binghamton.edu';
                $new_user->save();     
            }
            
            $sponsored_bu_group = Group::where('name','Sponsored Accounts in BU')->first();
            $sponsored_google_group = Group::where('name','Sponsored Accounts in Google')->first();

            // Create all SECONDARY/SPONSORED Account Users and Accounts
            foreach($data['users'] as $user) {
                $sponsor_user = UserUniqueId::where('name','bnumber')->where('value',$user['uniqueId'])->first();
                foreach($user['accounts'] as $account) {
                    if ($account['affiliation'] === 'Secondary Account') {
                        if ($account['system'] === 'PODS') {
                            $new_user = User::where('default_email',strtolower($account['username']).'@binghamton.edu')->orWhere('default_username',strtolower($account['username']))->first();
                            if (is_null($new_user)) {
                                $new_user = new User([
                                    'first_name' => strtolower($account['username']),
                                    'sponsored' => true,
                                    'sponsor_user_id' => $sponsor_user->user_id,
                                ]);
                                $new_user->save();
                            }
                            $new_user->default_username = strtolower($account['username']);
                            $new_user->save();
                            $new_account = new Account([
                                'user_id'=>$new_user->id,
                                'system_id'=>$bu_sys->id,
                                'account_id'=>strtolower($account['username']),
                                'default_username' => strtolower($account['username']),
                                'default_email' => strtolower($account['username']).'@binghamton.edu',
                            ]);
                            $new_account->save();
                            GroupMember::updateOrCreate(['group_id' => $sponsored_bu_group->id, 'user_id' => $new_user->id],[]);                            
                        } else if ($account['system'] === 'Google') {
                            $derived_username = strtolower(explode('@',$account['email'])[0]);
                            $new_user = User::where('default_email',strtolower($account['email']))->orWhere('default_username',$derived_username)->first();
                            if (is_null($new_user)) {
                                $new_user = new User([
                                    'first_name' => $derived_username,
                                    'sponsored' => true,
                                    'sponsor_user_id' => $sponsor_user->user_id,
                                    'default_username' => $derived_username,
                                    'default_email' => strtolower($account['email']),
                                ]);
                                $new_user->save();
                            }
                            $new_user->default_email = strtolower($account['email']);
                            $new_user->save();
                            $new_account = new Account([
                                'user_id'=>$new_user->id,
                                'system_id'=>$google_sys->id,
                                'account_id'=>strtolower($account['email'])
                            ]);
                            $new_account->save();
                            GroupMember::updateOrCreate(['group_id' => $sponsored_google_group->id, 'user_id' => $new_user->id],[]);                            
                        }
                    }
                } 
            }
        }
    }
}