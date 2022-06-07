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
        $default_username = null;
        $derived_username_substr = 
            strtolower(
                (isset($source_identity['first_name'][0])?$source_identity['first_name'][0]:'').
                (isset($source_identity['last_name'][0])?$source_identity['last_name'][0]:'').
                (isset($source_identity['last_name'][1])?$source_identity['last_name'][1]:'')
            );

        $accounts_rank = [];
        foreach($source_identity['accounts'] as $username => $account) {
            if ($account['primary'] === true) {
                $accounts_rank[$username] = 0;
                if (str_starts_with($username,$derived_username_substr)) { $accounts_rank[$username]+=1; } 
                if (!is_numeric(substr($username, -1, 1))) { $accounts_rank[$username]+=2; }
                if ($account['ad'] == true) { $accounts_rank[$username]+=3; }
                if ($account['google'] == true) { $accounts_rank[$username]+=3; }
            }
        }
        $ranks = [];
        foreach($accounts_rank as $username => $rank) {
            $ranks[$rank][] = $username;
        }
        if (count($ranks) > 0) {
            $max_rank = max(array_keys($ranks));
            $max_rank_accounts = $ranks[$max_rank];
            sort($max_rank_accounts);
            if (isset($max_rank_accounts[0])) {
                $default_username = $max_rank_accounts[0];
            }
        }
        return $default_username;
    }

    public function handle() {
        ini_set('memory_limit','2048M');
        if (!$this->confirm('Are you sure you want to load this data?  This action can not be undone')) {
            $this->error("Exiting");
            return;
        }
        $source_identities_file = $this->ask('What is the filename/filepath for the All Identities JSON File?');
        if (!file_exists($source_identities_file)) {
            $this->error("The specified file path does not exist.  Exiting");
            return;
        }

        $reserved_usernames = collect([]);
        if ($this->confirm('Would you also like to specify a reserved usernames file?')) {
            $reserved_usernames_file = $this->ask('What is the filename/filepath for the reserved usernames file?');
            if (!file_exists($reserved_usernames_file)) {
                $this->error("The specified file path does not exist.  Exiting");
                return;
            }
            $reserved_usernames = collect(explode("\n",file_get_contents($reserved_usernames_file)));
        }

        $this->info("Pre-Processing The Data...");
        $source_identities = json_decode(file_get_contents($source_identities_file),true);

        $bu_sys = System::where('name','BU')->first();
        $google_sys = System::where('name','Google Workspace')->first();
        $all_groups = Group::select('name','id','slug')->get();
        $alumni_email_group = Group::where('name','Alumni Email')->first();
        $alumni_ad_group = Group::where('name','Alumni AD')->first();
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
            } else if (isset($source_identity['millennium_id']) && !is_null($source_identity['millennium_id']) && $source_identity['millennium_id'] != '') {
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
            if (((is_null($source_identity['bnumber']) || $source_identity['bnumber'] == 'B00000000') && is_null($source_identity['millennium_id']) && is_null($source_identity['suny_id'])) ||
                (is_null($source_identity['first_name']) || is_null($source_identity['last_name']))) {
                $source_identity['skip_identity'] = true;
                $source_identity['is_orphan'] = true;
            }
            
            // Set Default metadata
            $new_identity_ids = [];
            if (!is_null($source_identity['bnumber']) && $source_identity['bnumber'] != 'B00000000') {
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
            /* Handle Alumni with an existing Google Account ==> Add to Alumni Email Group */
            /* Handle Alumni with an existing AD Account ==> Add to Alumni AD Group */
            foreach($source_identity['accounts'] as $username => $account) {
                if ($account['google'] == true && $account['primary'] == true) {
                    if ($account['vanity_alumni'] == true || 
                        in_array('alumni',$source_identity['affiliations']) || 
                        (isset($source_identity['millennium_id']) && !is_null($source_identity['millennium_id']) && $source_identity['millennium_id'] != '') ||
                        (in_array('alumni_associates',$source_identity['affiliations']) && count($source_identity['affiliations']) == 1) || 
                        (in_array('alumni_associates',$source_identity['affiliations']) && in_array('applicants',$source_identity['affiliations']))) {
                        $new_identity['groups'][] = ['group_id'=>$alumni_email_group->id,'name'=>$alumni_email_group->name];
                        break;
                    }
                }
            }
            foreach($source_identity['accounts'] as $username => $account) {
                if ($account['ad'] == true && $account['primary'] == true) {
                    if (in_array('alumni',$source_identity['affiliations']) || 
                        (isset($source_identity['millennium_id']) && !is_null($source_identity['millennium_id']) && $source_identity['millennium_id'] != '') ||
                        (in_array('alumni_associates',$source_identity['affiliations']) && count($source_identity['affiliations']) == 1)) {
                        $new_identity['groups'][] = ['group_id'=>$alumni_ad_group->id,'name'=>$alumni_ad_group->name];
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
        if (count($reserved_usernames)) {
            $this->info("Populating Reserved Usernames from Reserved Usernames File ...");
            $reserved_usernames_chunks = $reserved_usernames->chunk(100);
            $bar = $this->output->createProgressBar(count($reserved_usernames_chunks));
            foreach($reserved_usernames_chunks as $reserved_usernames_chunk) {
                $upsert_arr = [];
                foreach($reserved_usernames_chunk as $reserved_username) {
                    $upsert_arr[] = [
                        'username' => $reserved_username
                    ];
                }
                ReservedUsername::upsert($upsert_arr,'username');
                $bar->advance();
            }
            $this->info("\n");
            unset($reserved_usernames); unset($reserved_usernames_chunks);
        }

        $this->info("Populating Reserved Usernames From Identities File ...");
        $bar = $this->output->createProgressBar(count($source_identities));
        foreach($source_identities as $source_identity) {
            $upsert_arr = [];
            foreach($source_identity['accounts'] as $username => $account) {
                $upsert_arr[] = [
                    'username' => $username
                ];
                if (is_array($account['google_aliases'])) {
                    foreach($account['google_aliases'] as $google_alias) {
                        $upsert_arr[] = [
                            'username' => str_replace('@binghamton.edu','',$google_alias)
                        ];
                    }
                }
            }
            ReservedUsername::upsert($upsert_arr,'username');
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
            $upsert_arr = [];
            foreach($new_identity_arr['groups'] as $group) {
                $upsert_arr[] = [
                    'group_id'=>$group['group_id'],
                    'identity_id'=>$new_identity->id
                ];
            }
            GroupMember::upsert($upsert_arr,['group_id','identity_id']);

            // Create all accounts
            $upsert_arr = [];
            foreach($new_identity_arr['accounts'] as $account) {
                $upsert_arr[] = [
                    'identity_id'=>$new_identity->id,
                    'system_id'=>$account['system_id'],
                    'account_id'=>$account['account_id']
                ];
            }
            Account::upsert($upsert_arr,['identity_id','system_id','account_id']);

            // Create all entitlements
            $upsert_arr = [];
            foreach($new_identity_arr['entitlements'] as $entitlement) {
                $upsert_arr[] = [
                    'identity_id' => $new_identity->id,
                    'entitlement_id' => $entitlement['entitlement_id'],
                    'type' => $entitlement['type'],
                    'override' => $entitlement['override'],
                    'expire' => $entitlement['expire'],
                    'description' => $entitlement['description'],
                ];
            }
            IdentityEntitlement::upsert($upsert_arr,['identity_id','entitlement_id'],['type','override','expire','description']);

            // Recalculate Entitlements
            // $new_identity->recalculate_entitlements();
            $bar->advance();
        }
        $this->info("\n");
        $this->info("Loading Complete!");
    }
}