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

class InitBulkLoad extends Command
{
    protected $name = 'initbulkload';
    protected $description = 'Load the Initial Bulk Load File from Accounts Management / AD';

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
            if ($account['am_primary'] === true) {
                if (str_starts_with($username,$derived_username_substr)) { $matches_index = 'matches'; } 
                else { $matches_index = 'not_matches'; }
                if (is_numeric(substr($username, -1, 1))) {
                    $usernames[$matches_index]['numeric'][] = $username;
                } else {
                    $usernames[$matches_index]['non_numeric'][] = $username;
                }
            /* Handle situations where a person only has one account */
            } else if (count($source_identity['accounts']) == 1) {
                $numeric_usernames[] = $username;
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
            $default_username = 'ERROR';
        }
        return $default_username;
    }

    public function handle() {
        ini_set('memory_limit','1024M');
        if (!$this->confirm('Are you sure you want to load this data?  This action can not be undone')) {
            $this->error("Exiting");
            return;
        }
        $filename = $this->ask('What is the filename/filepath for the default JSON Bulk Load?');

        $this->info("Attempting to load the data...");
        $source_identities = json_decode(file_get_contents($filename),true);

        $bu_sys = System::where('name','BU')->first();
        $google_sys = System::where('name','Google Workspace')->first();
        $all_groups = Group::select('id','slug')->get();
        $alumni_email_group = Group::where('name','Alumni Email')->first();
        $ad_entitlement_mappings = [
            'Wireless-AdminNetAccess' => Entitlement::where('name','AdminNetAccess Wifi')->first(),
            'VPN-AdminNetAccess' => Entitlement::where('name','AdminNetAccess VPN')->first(),
            'VDI-Other' => Entitlement::where('name','Other Bingview')->first(),
            'VPN-rdp' => Entitlement::where('name','RDP Access')->first(),
            'Wireless-Deny' => Entitlement::where('name','Deny Wifi')->first(),
        ]; 

        // Create All PRIMARY Account Identities and Accounts
        foreach($source_identities as $source_identity) {
            // Decide if we want to continue with this person (they might be an orphan)
            $skip_identity = true;
            foreach($source_identity['accounts'] as $username => $account) {
                if (count($source_identity['affiliations']) > 0 || $account['vanity_alumni'] == true) {
                    $skip_identity = false;
                    break;
                }
            }
            if ($skip_identity == true) {
                continue;
            }
            
            // Set Default metadata
            $new_identity_ids = [];
            if (!is_null($source_identity['bnumber'])) {
                $new_identity_ids['bnumber'] = $source_identity['bnumber'];
            }
            if (!is_null($source_identity['millennium_id'])) {
                $new_identity_ids['unique_ids']['millennium_id'] = $source_identity['millennium_id'];
            }
            if (!is_null($source_identity['suny_id'])) {
                $new_identity_ids['unique_ids']['suny_id'] = $source_identity['suny_id'];
            }
            $default_username = $this->get_default_username($source_identity);
            $new_identity = new Identity([
                'first_name'=>$source_identity['first_name'],
                'last_name'=>$source_identity['last_name'],
                'ids' => $new_identity_ids,
                'type' => 'person',
                'sponsored' => false,
                'default_username' => $default_username,
                'default_email' => $default_username.'@binghamton.edu',
            ]);
            $new_identity->save();

            // Create all groups
            foreach($source_identity['affiliations'] as $group_slug) {
                $this_group = $all_groups->firstWhere('slug',$group_slug);
                // Make sure all groups exist in IAMBing!
                if (!is_null($this_group)) {
                    $new_group_membership = new GroupMember([
                        'group_id' => $this_group->id,
                        'identity_id' => $new_identity->id
                    ]);
                    $new_group_membership->save();
                }
            }
            $new_identity['groups'] = $source_identity['affiliations'];
            /* If you have a vanilty alumni email, or you have an active email account and you are
            a recognized alumni, add you to the "alumni_email" group */
            foreach($source_identity['accounts'] as $username => $account) {
                if ($account['am_primary'] === true && $account['google'] == true) {
                    if ($account['vanity_alumni'] == true || 
                        (in_array('alumni',$source_identity['affiliations']) && 
                        !in_array('staff',$source_identity['affiliations']) && 
                        !in_array('faculty',$source_identity['affiliations']))
                    ) {
                        GroupMember::updateOrCreate(['group_id'=>$alumni_email_group->id,'identity_id'=>$new_identity->id],[]);
                        break;
                    }
                }
            }

            // Create all accounts
            foreach($source_identity['accounts'] as $username => $account) {
                if ($account['am_primary'] === true) {
                    if($account['ad'] == true) {
                        $ad_account = new Account([
                            'identity_id'=>$new_identity->id,
                            'system_id'=>$bu_sys->id,
                            'account_id'=>strtolower($default_username)
                        ]);
                        $ad_account->save();
                    }
                    if($account['google'] == true) {
                        $google_account = new Account([
                            'identity_id'=>$new_identity->id,
                            'system_id'=>$google_sys->id,
                            'account_id'=>strtolower($default_username.'@binghamton.edu')
                        ]);
                        $google_account->save();
                    }
                }
            }

            // Add Entitlement Overrides
            foreach($source_identity['accounts'] as $username => $account) {
                if ($account['am_primary'] === true) {
                    if($account['ad'] == true) {
                        foreach($ad_entitlement_mappings as $ad_group => $iam_entitlement) {
                            if (in_array($ad_group,$account['ad_groups'])) {
                                $new_identity_entitlement = new IdentityEntitlement([
                                    'identity_id' => $new_identity->id,
                                    'entitlement_id' => $iam_entitlement->id,
                                    'type' => 'add',
                                    'override' => true,
                                    'expire' => false,
                                    'description' => 'Imported from BU AD based on Existing Groups ('.date('m/d/Y').')',
                                ]);
                                $new_identity_entitlement->save();
                            }
                        }
                    }
                }
            }
            $new_identity->recalculate_entitlements();
        }
    }
}