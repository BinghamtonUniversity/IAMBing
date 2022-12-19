<?php

namespace App\Http\Controllers;

use App\Models\Identity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use App\Models\Report;
use App\Models\Configuration;
use App\Models\System;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function get_all_reports(){
        return Report::all();  
    }
    
    public function get_report(Report $report){
        return Report::where('id',$report->id);
    }

    public function add_report(Request $request){
        $report = new Report($request->all());
        $report->save();
        return Report::where('id',$report->id)->first();
    }
    public function update_report(Request $request, Report $report){
        $report->update($request->all());
        return Report::where('id',$report->id)->first();
    }

    public function delete_report(Request $request, Report $report){
        $report->delete();
        return 'Success';
    }

    private function get_report_data(Report $report){
        ini_set('memory_limit','1024M');

        $include_group_ids = $report->config->include_group_ids;

        // Get Excluded Identities IDs
        $exclude_identity_ids = [];
        if (isset($report->config->exclude_group_ids)) {
            $exclude_identity_ids = DB::table('group_members')
                ->select('identity_id')
                ->whereIn('group_id',$report->config->exclude_group_ids)
                ->distinct()->orderBy('identity_id','asc')->get()->pluck('identity_id');
        }

        // Get Identities who are in any of the specified groups
        $identities = DB::table('group_members')->select('identity_id','group_id')
            ->leftJoin('groups','group_members.group_id','=','groups.id')
            ->whereIn('group_id',$include_group_ids)
            ->distinct()->orderBy('identity_id','asc')->get();

        // Match ALL group memberships (only return people who are in all groups)
        if ($report->config->groups_any_all == 'all') {
            $mapped_identities = [];
            foreach($identities as $identity) {
                if (!isset($mapped_identities[$identity->identity_id])) {
                    $mapped_identities[$identity->identity_id] = [];
                }
                $mapped_identities[$identity->identity_id][] = $identity->group_id;
            }
            $identities = collect();
            foreach($mapped_identities as $identity_id => $identity) {
                if (count($mapped_identities[$identity_id]) == count($include_group_ids)) {
                    $identities[] = $identity_id;
                }
            }
        } else {
            $identities = $identities->pluck('identity_id');
        }

        // Remove Excluded Group Memberships
        $identities = collect($identities)->diff($exclude_identity_ids)->values();

        // Get Included Identitiy IDs and Group Membership IDs
        $identity_groups = collect();
        collect($identities)->chunk(25000)->each(function($identities_chunk,$key) use (&$identity_groups) {
            $identity_groups = $identity_groups->concat(DB::table('group_members')
                ->select('identity_id','groups.id','groups.name','groups.slug')
                ->leftJoin('groups','group_members.group_id','=','groups.id')
                ->where(function($query) use ($identities_chunk) {
                    collect($identities_chunk)->chunk(1000)->each(function($item,$key) use ($query) {
                        $query->orWhereIn('identity_id',$item);
                    });
                })->distinct()->get());
        });

        // Get Included Identitiy IDs and Accounts
        $identity_accounts = collect();
        collect($identities)->chunk(25000)->each(function($identities_chunk,$key) use (&$identity_accounts) {
            $identity_accounts = $identity_accounts->concat(DB::table('accounts')
                ->select('identity_id','account_id','system_id')
                ->leftJoin('systems','accounts.system_id','systems.id')
                ->whereNull('accounts.deleted_at')
                ->where(function($query) use ($identities_chunk) {
                    collect($identities_chunk)->chunk(1000)->each(function($item,$key) use ($query) {
                        $query->orWhereIn('identity_id',$item);
                    });
                })->distinct()->get());
        });        

        // Get Included Identitiy IDs and Accounts
        $identity_unique_ids = collect();
        collect($identities)->chunk(25000)->each(function($identities_chunk,$key) use (&$identity_unique_ids) {
            $identity_unique_ids = $identity_unique_ids->concat(DB::table('identity_unique_ids')
                ->select('identity_id','name','value')
                ->whereNotNull('value')
                ->where(function($query) use ($identities_chunk) {
                    collect($identities_chunk)->chunk(1000)->each(function($item,$key) use ($query) {
                        $query->orWhereIn('identity_id',$item);
                    });
                })->distinct()->get());
        });

        // Get Raw Identities
        $identities_raw = collect();
        collect($identities)->chunk(25000)->each(function($identities_chunk,$key) use (&$identities_raw) {
            $identities_raw = $identities_raw->concat(DB::table('identities')
                ->select('identities.id', 'identities.iamid','identities.first_name','identities.last_name','identities.default_username','identities.default_email')
                ->leftJoin('group_members','identities.id','=','group_members.identity_id')
                ->leftJoin('groups','group_members.group_id','=','groups.id')
                ->where(function($query) use ($identities_chunk) {
                    collect($identities_chunk)->chunk(1000)->each(function($item,$key) use ($query) {
                        $query->orWhereIn('identity_id',$item);
                    });
                })->distinct()->get());  
        });      

        $identities_indexed = collect();
        foreach($identities_raw as $identity) {
            $identities_indexed[$identity->id] = $identity;
        }
        foreach($identity_groups as $identity_group) {
            $identities_indexed[$identity_group->identity_id]->groups[] = $identity_group;
        }
        foreach($identity_accounts as $identity_account) {
            $identities_indexed[$identity_account->identity_id]->accounts[$identity_account->system_id][] = $identity_account;
        }
        foreach($identity_unique_ids as $identity_unique_id) {
            $identities_indexed[$identity_unique_id->identity_id]->ids[$identity_unique_id->name] = $identity_unique_id;
        }

        return $identities_indexed;
    }  

    public function run_report(Request $request, Report $report) {
        $identities_indexed = $this->get_report_data($report);

        // Convert to CSV
        $configuration = Configuration::select('config')->where('name','identity_unique_ids')->first();
        if (is_null($configuration)) {
            abort(500,'Missing Unique IDs Configuration');
        }
        $unique_ids = collect($configuration->config)->mapWithKeys(function ($item, $key) {
            return [$item->name => $item->label];
        });
        $systems = System::select('id','name')->get()->mapWithKeys(function ($item, $key) {
            return [$item->id => $item->name];
        });

        // Build CSV Header Columns
        $columns = [
            'ID','IAMID','First Name','Last Name','Default Username','Default Email'
        ];
        foreach($unique_ids as $name => $label) {
            $columns[] = $label;
        }
        foreach($systems as $system_id => $system_name) {
            $columns[] = $system_name.' Accounts';
        }
        $columns[] = 'Group Memberships';

        // Build CSV Rows
        $rows = [];
        $rows[] = $columns;
        foreach($identities_indexed as $identity) {
            $row = [
                $identity->id,
                $identity->iamid,
                $identity->first_name,
                $identity->last_name,
                $identity->default_username,
                $identity->default_email,
            ];
            foreach($unique_ids as $name => $label) {
                if (isset($identity->ids[$name])) {
                    $row[] = $identity->ids[$name]->value;
                } else {
                    $row[] = '';
                }
            }
            foreach($systems as $system_id => $system_name) {
                if (isset($identity->accounts[$system_id])) {
                    $row[] = implode(',',Arr::pluck($identity->accounts[$system_id],'account_id'));
                } else {
                    $row[] = '';
                }
            }

            $row[] = implode(',',Arr::pluck($identity->groups,'name'));
            $rows[] = $row;  
        }

        // Build CSV Formatted Rows
        $csv_rows = [];
        foreach($rows as $row) {
            $csv_rows[] = '"'.implode('","',array_values($row)).'"';
        }
        return response(implode("\n",$csv_rows), 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition','attachment; filename="'.$report->name.' Report - '.Carbon::now()->toDateString().'.csv');
    }

    public function run_report2(Request $request, Report $report) {
        $identities = $this->get_report_data($report);

        // Convert to CSV
        $configuration = Configuration::select('config')->where('name','identity_unique_ids')->first();
        if (is_null($configuration)) {
            abort(500,'Missing Unique IDs Configuration');
        }
        $unique_ids = collect($configuration->config)->mapWithKeys(function ($item, $key) {
            return [$item->name => $item->label];
        });
        $systems = System::select('id','name')->get()->mapWithKeys(function ($item, $key) {
            return [$item->id => $item->name];
        });

        $columns = [
            'ID','IAMID','First Name','Last Name'
        ];
        foreach($unique_ids as $name => $label) {
            $columns[] = $label;
        }
        $columns[] = 'Group'; $columns[] = 'System'; $columns[] = 'Account';

        $rows = [];
        $rows[] = $columns;
        foreach($identities as $identity) {
            foreach($identity->groups as $group) {
                if (!isset($identity->accounts)) {
                    $row = [
                        $identity->id,
                        $identity->iamid,
                        $identity->first_name,
                        $identity->last_name,
                    ];
                    foreach($unique_ids as $name => $label) {
                        if (isset($identity->ids[$name])) {
                            $row[] = $identity->ids[$name]->value;
                        } else {
                            $row[] = '';
                        }
                    }
                    $row[] = $group->name;
                    $row[] = '';
                    $row[] = '';
                    $rows[] = $row;
                } else {
                    foreach($identity->accounts as $system_id => $system_accounts) {
                        foreach($system_accounts as $account) {
                            $row = [
                                $identity->id,
                                $identity->iamid,
                                $identity->first_name,
                                $identity->last_name,
                            ];
                            foreach($unique_ids as $name => $label) {
                                if (isset($identity->ids[$name])) {
                                    $row[] = $identity->ids[$name]->value;
                                } else {
                                    $row[] = '';
                                }
                            }
                            $row[] = $group->name;
                            $row[] = $systems[$system_id];
                            $row[] = $account->account_id;
                            $rows[] = $row;
                        }
                    }
                }
            }
        }

        // Build CSV Formatted Rows
        $csv_rows = [];
        foreach($rows as $row) {
            $csv_rows[] = '"'.implode('","',array_values($row)).'"';
        }
        return response(implode("\n",$csv_rows), 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition','attachment; filename="'.$report->name.' Report - '.Carbon::now()->toDateString().'.csv');

    }
}
