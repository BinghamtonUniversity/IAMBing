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
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    private function get_spreadsheet_config()
    {
        // Use This tool to convert the entitlement spreadsheet into a PHP Array: https://t.yctin.com/en/excel/to-php-array/
        // Entitlement Spreadsheet: https://docs.google.com/spreadsheets/d/1yD-TTZ8B8hRXI7JX8wAf-9Hu_nJWfXfxyYbDTRCW6WA/edit#gid=0
        return array(
            0 => array('', '', 'faculty', 'faculty', 'staff', 'student', 'student', 'student', 'staff', 'alum', 'alum', 'applicant', 'applicant', 'affiliate', 'affiliate', 'affiliate', 'affiliate', 'affiliate', 'affiliate', 'affiliate'),
            1 => array('System', 'Entitlement', 'Faculty', 'Recent Faculty', 'Staff', 'Students', 'Online Students', 'Currently Enrolled Students', 'Student Staff', 'Alumni', 'Recent Alumni', 'Applicants', 'Admitted Applicants', 'Rf Staff', 'Retirees', 'Volunteers', 'Live On Campus', 'Visiting Scholars Professionals', 'Alumni Associates', 'Alumni Email'),
            2 => array('BU', 'BU Account', 'x', 'x', 'x', 'x', 'x', 'x', '', '', 'x', 'x', 'x', 'x', 'x', 'x', 'x', '', '', 'x'),
            3 => array('BU', 'Enforce 2FA', 'x', 'x', 'x', 'x', 'x', 'x', '', '', 'x', '', '', 'x', 'x', 'x', 'x', '', '', 'x'),
            4 => array('BU', 'Employee Wifi', 'x', '', 'x', '', '', '', '', '', '', '', '', 'x', '', 'x', '', '', '', ''),
            5 => array('BU', 'Employee VPN', 'x', '', 'x', '', '', '', '', '', '', '', '', 'x', '', '', '', '', '', ''),
            6 => array('BU', 'Faculty Bingview', 'x', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
            7 => array('BU', 'Staff Bingview', '', '', 'x', '', '', '', '', '', '', '', '', 'x', '', '', '', '', '', ''),
            8 => array('BU', 'Student Wifi', '', '', '', 'x', 'x', 'x', '', '', 'x', '', '', '', '', '', '', '', '', ''),
            9 => array('BU', 'Student VPN', '', '', '', 'x', 'x', 'x', '', '', 'x', '', '', '', '', '', '', '', '', ''),
            10 => array('BU', 'Student Bingview', '', '', '', 'x', 'x', 'x', '', '', '', '', '', '', '', '', '', '', '', ''),
            11 => array('BU', 'Interactive Login', 'x', '', 'x', 'x', 'x', 'x', '', '', '', '', '', 'x', '', 'x', '', '', '', ''),
            12 => array('BU', 'Spectrum Access', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'x', '', '', ''),
            13 => array('BU', 'AdminNetAccess Wifi', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
            14 => array('BU', 'AdminNetAccess VPN', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
            15 => array('BU', 'Other Bingview', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
            16 => array('BU', 'RDP Access', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
            17 => array('BU', 'Deny Wifi', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
            18 => array('Google Workspace', 'Google Account', 'x', 'x', 'x', 'x', 'x', 'x', '', '', 'x', '', '', 'x', 'x', 'x', '', '', '', 'x'),
            19 => array('Google Workspace', 'Google TwoStep', 'x', 'x', 'x', 'x', 'x', 'x', '', '', 'x', '', '', 'x', 'x', 'x', '', '', '', 'x'),
            20 => array('Google Workspace', 'Google Email', 'x', 'x', 'x', 'x', 'x', 'x', '', '', 'x', '', '', 'x', 'x', 'x', '', '', '', 'x'),
            21 => array('Google Workspace', 'Google Drive', 'x', '', 'x', 'x', 'x', 'x', '', '', 'x', '', '', 'x', '', 'x', '', '', '', ''),
            22 => array('Google Workspace', 'Google Sites', 'x', '', 'x', '', '', '', '', '', '', '', '', 'x', '', 'x', '', '', '', ''),
            23 => array('Google Workspace', 'Google Chat', 'x', '', 'x', 'x', 'x', 'x', '', '', 'x', '', '', 'x', '', 'x', '', '', '', ''),
            24 => array('Google Workspace', 'Google Groups', 'x', '', 'x', 'x', '', 'x', '', '', '', '', '', 'x', '', 'x', '', '', '', ''),
            25 => array('Google Workspace', 'Google Calendar', 'x', '', 'x', 'x', 'x', 'x', '', '', 'x', '', '', 'x', '', 'x', '', '', '', ''),
            26 => array('Google Workspace', 'Google Classroom', 'x', '', 'x', 'x', 'x', 'x', '', '', 'x', '', '', 'x', '', 'x', '', '', '', ''),
            27 => array('Google Workspace', 'Google Addons', 'x', '', 'x', 'x', 'x', 'x', '', '', 'x', '', '', 'x', '', 'x', '', '', '', ''),
            28 => array('Google Workspace', 'Google Vault', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
            29 => array('Banner', 'UNIV Email', '', '', '', 'x', 'x', 'x', '', '', 'x', 'x', 'x', '', '', '', 'x', '', '', 'x'),
            30 => array('Banner', 'EMPL Email', 'x', 'x', 'x', '', '', '', '', '', '', '', '', 'x', 'x', 'x', '', '', '', ''),
        );
    }

    public function run()
    {
        $default_username_template = new Configuration(['name' => 'default_username_template', 'config' => '{{first_name.0}}{{last_name.0}}{{last_name.1}}{{last_name.2}}{{last_name.3}}{{last_name.4}}{{last_name.5}}{{last_name.6}}{{last_name.7}}{{last_name.8}}{{last_name.9}}{{last_name.10}}{{#iterator}}{{iterator}}{{/iterator}}']);
        $default_username_template->save();
        $identity_attributes = new Configuration(['name' => 'identity_attributes', 'config' => []]);
        $identity_attributes->save();
        $identity_unique_ids = new Configuration(['name' => 'identity_unique_ids', 'config' => [['name' => 'bnumber', 'label' => 'BNumber'], ['name' => 'suny_id', 'label' => 'SUNY ID'], ['name' => 'millennium_id', 'label' => 'Millennium ID']]]);
        $identity_unique_ids->save();
        $affiliations = new Configuration(['name' => 'affiliations', 'config' => ['faculty', 'staff', 'student', 'employee', 'member', 'affiliate', 'alum', 'library-walk-in', 'applicant']]);
        $affiliations->save();
        $username_availability = new Configuration(['name' => 'username_availability', 'config' => ['path' => '/users/username_info', 'verb' => 'GET', 'endpoint' => '1', 'available_response' => 404, 'not_available_response' => 200]]);
        $username_availability->save();

        // Create Seed Identities for Ali Kemal and Tim
        $identities = [];
        $identities['tim'] = new Identity(['first_name' => 'Ali Kemal', 'last_name' => 'Tanriverdi', 'attributes' => [], 'default_username' => 'atanrive', 'default_email' => 'atanrive@binghamton.edu']);
        $identities['tim']->save();
        $identities['ali'] = new Identity(['first_name' => 'Tim', 'last_name' => 'Cortesi', 'attributes' => [], 'default_username' => 'tcortesi', 'default_email' => 'tcortesi@binghamton.edu']);
        $identities['ali']->save();
        $example_unique_id = new IdentityUniqueID(['identity_id' => $identities['tim']->id, 'name' => "bnumber", 'value' => "B00450942"]);
        $example_unique_id->save();
        $example_unique_id2 = new IdentityUniqueID(['identity_id' => $identities['ali']->id, 'name' => "bnumber", 'value' => "B00505893"]);
        $example_unique_id2->save();
        $permission = new Permission(['identity_id' => $identities['tim']->id, 'permission' => "manage_identity_permissions"]);
        $permission->save();
        $permission2 = new Permission(['identity_id' => $identities['ali']->id, 'permission' => "manage_identity_permissions"]);
        $permission2->save();

        // Create Seed Endpoints
        $endpoint1 = new Endpoint(['name' => 'DataProxy Default', 'config' => ['content_type' => 'application/x-www-form-urlencoded', 'secret' => '', 'type' => 'http_basic_auth', 'url' => 'https://hermesdev.binghamton.edu/iam', 'username' => '', ]]);
        $endpoint1->save();

        // Create Seed Systems
        $systems = [];
        $systems['BU'] = new System(['name' => 'BU', 'default_account_id_template' => '{{default_username}}', 'onremove' => 'delete', 'config' => ['actions' => [['path' => '/ad/user/{{account.account_id}}', 'verb' => 'PUT', 'action' => 'create', 'endpoint' => '1', 'response_code' => 200, ], ['path' => '/ad/user/{{account.account_id}}', 'verb' => 'PUT', 'action' => 'update', 'endpoint' => '1', 'response_code' => 200, ], ['path' => '/ad/user/{{account.account_id}}', 'verb' => 'DELETE', 'action' => 'delete', 'endpoint' => '1', 'response_code' => 200, ], ['path' => '/ad/user/{{account.account_id}}', 'verb' => 'GET', 'action' => 'info', 'endpoint' => '1', 'response_code' => 200, ]]]]);
        $systems['BU']->save();
        $systems['Google Workspace'] = new System(['name' => 'Google Workspace', 'default_account_id_template' => '{{default_username}}@binghamton.edu', 'onremove' => 'delete', 'config' => ['actions' => [['path' => '/google/user/{{account.account_id}}@binghamton.edu', 'verb' => 'GET', 'action' => 'info', 'endpoint' => '1', 'response_code' => 200, ], ['path' => '/google/user/{{account.account_id}}@binghamton.edu', 'verb' => 'PUT', 'action' => 'update', 'endpoint' => '1', 'response_code' => 200, ], ['path' => '/google/user/{{account.account_id}}@binghamton.edu', 'verb' => 'PUT', 'action' => 'create', 'endpoint' => '1', 'response_code' => 200, ], ]]]);
        $systems['Google Workspace']->save();
        $systems['Banner'] = new System(['name' => 'Banner', 'default_account_id_template' => '{{ids.bnumber}}', 'onremove' => 'delete', 'config' => ['actions' => [['path' => '/banner/goremal/{{default_username}}', 'verb' => 'GET', 'action' => 'info', 'endpoint' => '1', 'response_code' => 200, ], ['path' => '/banner/goremal', 'verb' => 'POST', 'action' => 'create', 'endpoint' => '1', 'response_code' => 200, ], ['path' => '/banner/goremal', 'verb' => 'PUT', 'action' => 'update', 'endpoint' => '1', 'response_code' => 200, ]]]]);
        $systems['Banner']->save();

        $spreadsheet_config = $this->get_spreadsheet_config();

        // Create all Groups
        $groups = [];
        for ($column = 2;$column < count($spreadsheet_config[1]);$column++)
        {
            $group_name = $spreadsheet_config[1][$column];
            $affiliation = (isset($spreadsheet_config[0][$column]) ? $spreadsheet_config[0][$column] : null);
            $groups[$column] = new Group(['slug' => Str::snake($group_name) , 'name' => $group_name, 'affiliation' => $affiliation, 'order' => ($column - 0) ]);
            $groups[$column]->save();
        }

        // Create all Entitlements
        $entitlements = [];
        for ($row = 2;$row < count($spreadsheet_config);$row++)
        {
            $system_name = $spreadsheet_config[$row][0];
            $entitlement_name = $spreadsheet_config[$row][1];
            $entitlements[$row] = new Entitlement(['name' => $entitlement_name, 'system_id' => $systems[$system_name]->id]);
            $entitlements[$row]->save();
        }

        // Create Group Entitlements
        $group_entitlements = [[]];
        for ($row = 2;$row < count($spreadsheet_config);$row++)
        {
            for ($column = 2;$column < count($spreadsheet_config[2]);$column++)
            {
                if ($spreadsheet_config[$row][$column] == 'x')
                {
                    $group_entitlements[$row][$column] = new GroupEntitlement(['group_id' => $groups[$column]->id, 'entitlement_id' => $entitlements[$row]->id]);
                    $group_entitlements[$row][$column]->save();
                }
            }
        }
        dd($group_entitlements);
    }
}