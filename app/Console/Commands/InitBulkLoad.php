<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Identity;
use App\Models\System;
use App\Models\Account;
use App\Models\IdentityUniqueID;
use App\Models\GroupMember;
use App\Models\Group;
use App\Models\IdentityEntitlement;
use App\Models\Entitlement;
use App\Models\ReservedUsername;

class InitBulkLoad extends Command
{
    protected $name = 'initbulkload';
    protected $description = 'Load the Initial Bulk Load File from Accounts Management / AD';

    private function array_to_csv($data) {
        $csv_rows = [];
        $csv_rows[] = implode(',',array_keys($data[0]));
        foreach($data as $row_index => $row) {
            $csv_rows[] = '"'.implode('","',str_replace('"','""',$row)).'"';
        }
        return implode("\n",$csv_rows);
    }

    private function get_default_username($source_identity) {
        $usernames = [
            'matches' => ['numeric'=>[],'non_numeric'=>[]],
            'not_matches' => ['numeric'=>[],'non_numeric'=>[]]
        ];
        $derived_username_substr = 
            strtolower(
                (isset($source_identity['first_name'][0])?$source_identity['first_name'][0]:'').
                (isset($source_identity['last_name'][0])?$source_identity['last_name'][0]:'').
                (isset($source_identity['last_name'][1])?$source_identity['last_name'][1]:'')
            );

        foreach($source_identity['accounts'] as $username => $account) {
            if ($account['primary'] === true) {
                if (str_starts_with($username,$derived_username_substr)) { $matches_index = 'matches'; } 
                else { $matches_index = 'not_matches'; }
                if (is_numeric(substr($username, -1, 1))) {
                    $usernames[$matches_index]['numeric'][] = $username;
                } else {
                    $usernames[$matches_index]['non_numeric'][] = $username;
                }
            /* Handle situations where a person only has one account */
            } else if (count($source_identity['accounts']) == 1) {
                $usernames['matches']['non_numeric'][] = $username;
                break;
            }
        }
        sort($usernames['matches']['numeric']); sort($usernames['matches']['non_numeric']);
        sort($usernames['not_matches']['numeric']); sort($usernames['not_matches']['non_numeric']);

        if (count($usernames['matches']['non_numeric'])>0) {
            $default_username = $usernames['matches']['non_numeric'][0];
        } else if (count($usernames['not_matches']['non_numeric'])>0) {
            $default_username = $usernames['not_matches']['non_numeric'][0];
        } else if (count($usernames['matches']['numeric'])>0) {
            $default_username = $usernames['matches']['numeric'][0];
        } else if (count($usernames['not_matches']['numeric'])>0) {
            $default_username = $usernames['not_matches']['numeric'][0];
        } else {
            $default_username = null;
        }
        return $default_username;
    }

    public function handle() {
        ini_set('memory_limit','2048M');
        if (!$this->confirm('Are you sure you want to load this data?  This action can not be undone')) {
            $this->error("Exiting");
            return;
        }
        $filename = $this->ask('What is the filename/filepath for the default JSON Bulk Load?');

        $this->info("Pre-Processing The Data...");
        $source_identities = json_decode(file_get_contents($filename),true);

        $bu_sys = System::where('name','BU')->first();
        $google_sys = System::where('name','Google Workspace')->first();
        $all_groups = Group::select('name','id','slug')->get();
        $alumni_email_group = Group::where('name','Alumni Email')->first();
        $ad_entitlement_mappings = [
            'Wireless-AdminNetAccess' => Entitlement::where('name','Wireless-AdminNetAccess')->first(),
            'VPN-AdminNetAccess' => Entitlement::where('name','VPN-AdminNetAccess')->first(),
            'VDI-Other' => Entitlement::where('name','VDI-Other')->first(),
            'VPN-rdp' => Entitlement::where('name','VPN-rdp')->first(),
            'Wireless-Deny' => Entitlement::where('name','Wireless-Deny')->first(),
        ]; 

        $new_identities = []; $orphaned_accounts = []; $secondary_accounts = [];
        // Create All PRIMARY Account Identities and Accounts
        $bar = $this->output->createProgressBar(count($source_identities));
        foreach($source_identities as $source_identity) {
            // Decide if the identity is an orphan
            $is_orphan = true;
            if (count($source_identity['affiliations']) > 0) {
                $is_orphan = false;
            } else {
                $no_ad_or_google_accounts = true;
                foreach($source_identity['accounts'] as $username => $account) {
                    if ($account['vanity_alumni'] == true) {
                        $is_orphan = false;
                        break;
                    }
                    if ($account['ad'] == true || $account['google'] == true) {
                        $no_ad_or_google_accounts = false;
                        break;
                    }
                }   
                if ($no_ad_or_google_accounts == true && count($source_identity['accounts']) > 0) {
                    $is_orphan = false;
                }
            }
            $source_identity['is_orphan'] = $is_orphan;
            
            // Check to see if we should include this person in IAMBing
            if ((is_null($source_identity['bnumber']) && is_null($source_identity['millennium_id']) && is_null($source_identity['suny_id'])) ||
                (is_null($source_identity['first_name']) || is_null($source_identity['last_name']))) {
                $source_identity['skip_identity'] = true;
            }
            
            // Set Default metadata
            $new_identity_ids = [];
            if (!is_null($source_identity['bnumber'])) {
                $new_identity_ids['bnumber'] = $source_identity['bnumber'];
            }
            if (!is_null($source_identity['millennium_id'])) {
                $new_identity_ids['millennium_id'] = $source_identity['millennium_id'];
            }
            if (!is_null($source_identity['suny_id'])) {
                $new_identity_ids['suny_id'] = $source_identity['suny_id'];
            }
            $default_username = $this->get_default_username($source_identity);
            $new_identity = [
                'first_name'=>$source_identity['first_name'],
                'last_name'=>$source_identity['last_name'],
                'ids' => $new_identity_ids,
                'type' => 'person',
                'sponsored' => false,
                'default_username' => $default_username,
                'default_email' => is_null($default_username)?null:$default_username.'@binghamton.edu',
                'groups' => [],
                'accounts' => [],
                'entitlements' => [],
            ];

            // Create all groups
            foreach($source_identity['affiliations'] as $group_slug) {
                $this_group = $all_groups->firstWhere('slug',$group_slug);
                // Make sure all groups exist in IAMBing!
                if (!is_null($this_group)) {
                    $new_identity['groups'][] = ['group_id' => $this_group->id,'name'=>$this_group->name];
                }
            }
            /* If you have a vanilty alumni email, or you have an active email account and you are
            a recognized alumni, add you to the "alumni_email" group */
            foreach($source_identity['accounts'] as $username => $account) {
                if ($account['google'] == true) {
                    if ($account['vanity_alumni'] == true || 
                        (in_array('alumni',$source_identity['affiliations']) && 
                        !in_array('staff',$source_identity['affiliations']) && 
                        !in_array('faculty',$source_identity['affiliations']))
                    ) {
                        $new_identity['groups'][] = ['group_id'=>$alumni_email_group->id,'name'=>$alumni_email_group->name];
                        break;
                    }
                }
            }

            // Create all accounts
            foreach($source_identity['accounts'] as $username => $account) {
                if ($account['primary'] === true) {
                    if ($source_identity['is_orphan']) {
                        if ($account['google'] == true || $account['ad'] == true) {
                            $orphaned_accounts[] = [
                                'first_name' => $source_identity['first_name'],
                                'last_name' => $source_identity['last_name'],
                                'bnumber' => $source_identity['bnumber'],
                                'username' => $account['username'],
                                'bu' => $account['ad']?'X':'',
                                'google' => $account['google']?'X':'',
                            ];
                        }
                    } else {
                        if($account['ad'] == true) {
                            $new_identity['accounts'][] = [
                                'name'=>$bu_sys->name,
                                'system_id'=>$bu_sys->id,
                                'account_id'=>strtolower($username)
                            ];
                        }
                        if($account['google'] == true) {
                            $new_identity['accounts'][] = [
                                'name'=>$google_sys->name,
                                'system_id'=>$google_sys->id,
                                'account_id'=>strtolower($username.'@binghamton.edu')
                            ];
                        }
                    }
                } else {
                    if ($account['google'] == true || $account['ad'] == true) {
                        $secondary_accounts[] = [
                            'first_name' => $source_identity['first_name'],
                            'last_name' => $source_identity['last_name'],
                            'bnumber' => $source_identity['bnumber'],
                            'username' => $account['username'],
                            'bu' => $account['ad']?'X':'',
                            'google' => $account['google']?'X':'',
                        ];
                    }
                }
            }

            // Add Entitlement Overrides
            foreach($source_identity['accounts'] as $username => $account) {
                if ($account['primary'] === true) {
                    if($account['ad'] == true) {
                        foreach($ad_entitlement_mappings as $ad_group => $iam_entitlement) {
                            if (in_array($ad_group,$account['ad_groups'])) {
                                $new_identity['entitlements'][] = [
                                    'name'=>$iam_entitlement->name,
                                    'entitlement_id' => $iam_entitlement->id,
                                    'type' => 'add',
                                    'override' => true,
                                    'expire' => false,
                                    'description' => 'Imported from BU AD based on Existing Groups ('.date('m/d/Y').')',
                                ];
                            }
                        }
                    }
                }
            }
            if (isset($source_identity['skip_identity']) && $source_identity['skip_identity'] == true) {
                $bar->advance();
                continue;
            } else {
                $new_identities[] = $new_identity;
                $bar->advance();
            }
        }

        $this->info("\n");
        $this->info("Saving All (".count($new_identities).") Identities To A File ...");
        file_put_contents('new_data.json',json_encode($new_identities,JSON_PRETTY_PRINT));
        $this->info("Saving All (".count($orphaned_accounts).") Orphaned Accounts To A File ...");
        file_put_contents('orphaned_accounts.csv',$this->array_to_csv($orphaned_accounts));
        $this->info("Saving All (".count($secondary_accounts).") Secondary Accounts To A File ...");
        file_put_contents('secondary_accounts.csv',$this->array_to_csv($secondary_accounts));

        // SAVE EVERYTHING to Database!
        // Populate Reserved Usernames:
        $this->info("Populating Reserved Usernames ...");
        $bar = $this->output->createProgressBar(count($source_identities));
        foreach($source_identities as $source_identity) {
            foreach($source_identity['accounts'] as $username => $account) {
                ReservedUsername::updateOrCreate([
                    'username' => $username
                ],[]);
                if (is_array($account['google_aliases'])) {
                    foreach($account['google_aliases'] as $google_alias) {
                        ReservedUsername::updateOrCreate([
                            'username' => str_replace('@binghamton.edu','',$google_alias)
                        ],[]);
                    }
                }
            }
            $bar->advance();
        }
        $this->info("\n");

        // Create All PRIMARY Account Identities and Accounts
        $this->info("Saving the Data to the Database ...");
        $bar = $this->output->createProgressBar(count($new_identities));
        foreach($new_identities as $new_identity_arr) {
            // Create or Update Identity
            $new_identity = null;
            if (isset($new_identity_arr['ids']['bnumber'])) {
                $new_identity = Identity::where(function ($query) use ($new_identity_arr) {
                    $query->whereHas('identity_unique_ids', function($q) use ($new_identity_arr){
                        $q->where('name','bnumber')->where('value',$new_identity_arr['ids']['bnumber']);
                    });
                })->first();
            }
            if (is_null($new_identity)) {
                $new_identity = new Identity([
                    'first_name'=>$new_identity_arr['first_name'],
                    'last_name'=>$new_identity_arr['last_name'],
                    'ids' => $new_identity_arr['ids'],
                    'type' => $new_identity_arr['type'],
                    'sponsored' => $new_identity_arr['sponsored'],
                    'default_username' => $new_identity_arr['default_username'],
                    'default_email' => $new_identity_arr['default_email'],
                ]);
                $new_identity->save();
            } else {
                $new_identity->update([
                    'first_name'=>$new_identity_arr['first_name'],
                    'last_name'=>$new_identity_arr['last_name'],
                    'ids' => $new_identity_arr['ids'],
                    'type' => $new_identity_arr['type'],
                    'sponsored' => $new_identity_arr['sponsored'],
                    'default_username' => $new_identity_arr['default_username'],
                    'default_email' => $new_identity_arr['default_email'],
                ]);
            }
            // Create all groups
            foreach($new_identity_arr['groups'] as $group) {
                GroupMember::updateOrCreate([
                    'group_id'=>$group['group_id'],
                    'identity_id'=>$new_identity->id
                ],[]);
            }
            // Create all accounts
            foreach($new_identity_arr['accounts'] as $account) {
                Account::updateOrCreate([
                    'identity_id'=>$new_identity->id,
                    'system_id'=>$account['system_id'],
                    'account_id'=>$account['account_id']
                ],[]);
            }
            // Create all entitlements
            foreach($new_identity_arr['entitlements'] as $entitlement) {
                IdentityEntitlement::updateOrCreate([
                    'identity_id' => $new_identity->id,
                    'entitlement_id' => $entitlement['entitlement_id'],
                ],[
                    'type' => $entitlement['type'],
                    'override' => $entitlement['override'],
                    'expire' => $entitlement['expire'],
                    'description' => $entitlement['description'],
                ]);
            }
            // Recalculate Entitlements
            $new_identity->recalculate_entitlements();
            $bar->advance();
        }
        $this->info("\n");
        $this->info("Loading Complete!");
    }
}