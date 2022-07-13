<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\IdentityController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\EntitlementController;
use App\Http\Controllers\EndpointController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\CASController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\GroupActionQueueController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::any('/login', [CASController::class, 'login']);
Route::get('/logout',[CASController::class, 'logout']);

Route::group(['middleware'=>['custom.auth']], function () {

    /* Admin Pages */
    Route::get('/', [AdminController::class, 'admin']);
    Route::get('/identities/{identity?}', [AdminController::class, 'identities'])->middleware('can:view_in_admin,App\Models\Identity');
    Route::get('/identities/{identity}/accounts', [AdminController::class, 'identity_accounts'])->middleware('can:manage_identity_accounts,App\Models\Identity');
    Route::get('/identities/{identity}/groups', [AdminController::class, 'identity_groups'])->middleware('can:view_in_admin,App\Models\Identity');
    Route::get('/identities/{identity}/permissions', [AdminController::class, 'identity_permissions'])->middleware('can:manage_identity_permissions,App\Models\Identity');
    Route::get('/identities/{identity}/entitlements', [AdminController::class, 'identity_entitlements'])->middleware('can:override_identity_entitlements,App\Models\Identity');
    Route::get('/groups', [AdminController::class, 'groups'])->middleware('can:list_search,App\Models\Group');
    Route::get('/groups/{group}/members', [AdminController::class, 'group_members'])->middleware('can:manage_group_members,group');
    Route::get('/groups/{group}/admins', [AdminController::class, 'group_admins'])->middleware('can:manage_groups,App\Models\Group');
    Route::get('/groups/{group}/entitlements', [AdminController::class, 'group_entitlements'])->middleware('can:manage_group_entitlements,group');
    Route::get('/systems', [AdminController::class, 'systems'])->middleware('can:list_search,App\Models\System');
    Route::get('/entitlements', [AdminController::class, 'entitlements'])->middleware('can:list_search,App\Models\Entitlement');
    Route::get('/entitlements/{entitlement}/groups', [AdminController::class, 'entitlement_groups'])->middleware('can:manage_group_entitlements,App\Models\Group');
    Route::get('/entitlements/{entitlement}/overrides', [AdminController::class, 'entitlement_overrides'])->middleware('can:list_search,App\Models\Entitlement');
    Route::get('/endpoints', [AdminController::class, 'endpoints'])->middleware('can:list_search,App\Models\Endpoint');
    Route::get('/configuration', [AdminController::class, 'configuration'])->middleware('can:update,App\Models\Configuration');
    Route::get('/identities/{identity}/logs',[AdminController::class,'identity_logs'])->middleware('can:view,App\Models\Log');
    Route::get('/group_action_queue', [AdminController::class, 'group_action_queue'])->middleware('can:view_in_admin,App\Models\GroupActionQueue');
    Route::get('/group_action_queue/download_csv',[GroupActionQueueController::class,'download_queue'])->middleware('can:view_in_admin,App\Models\GroupActionQueue');

    Route::group(['prefix' => 'api'], function () {
        /* Identity Methods */
        Route::get('/identities/{identity}/dashboard', [IdentityController::class,'get_identity'])->middleware('can:view_identity_dashboard,App\Models\Identity');
        Route::get('/identities','IdentityController@get_all_identities')->middleware('can:view_in_admin,App\Models\Identity');
        Route::get('/identities/search/{search_string?}',[IdentityController::class,'search'])->middleware('can:list_search,App\Models\Identity');
        Route::get('/identities/{identity}',[IdentityController::class,'get_identity'])->middleware('can:view_identity_info,App\Models\Identity');
        Route::post('/identities',[IdentityController::class,'add_identity'])->middleware('can:add_identities,App\Models\Identity');
        Route::put('/identities/{identity}',[IdentityController::class,'update_identity'])->middleware('can:update_identities,App\Models\Identity');
        Route::delete('/identities/{identity}',[IdentityController::class,'delete_identity'])->middleware('can:delete_identities,App\Models\Identity');
        //Identity Permissions
        Route::put('/identities/{identity}/permissions',[IdentityController::class,'set_permissions'])->middleware('can:manage_identity_permissions,App\Models\Identity');
        Route::get('/identities/{identity}/permissions',[IdentityController::class,'get_permissions'])->middleware('can:manage_identity_permissions,App\Models\Identity');
        //Merge Identity
        Route::put('/identities/{source_identity}/merge_into/{target_identity}',[IdentityController::class,'merge_identity'])->middleware('can:merge_identities,App\Models\Identity');
        //Impersonate
        Route::post('/login/{identity}',[IdentityController::class,'login_identity'])->middleware('can:impersonate_identities,App\Models\Identity');

        // Identity Accounts
        Route::get('/identities/{identity}/accounts',[IdentityController::class,'get_accounts'])->middleware('can:view_identity_info,App\Models\Identity');
        Route::get('/identities/{identity}/accounts/{account_id}',[IdentityController::class,'get_account'])->middleware('can:view_identity_info,App\Models\Identity');
        Route::post('/identities/{identity}/accounts',[IdentityController::class,'add_account'])->middleware('can:manage_identity_accounts,App\Models\Identity');
        Route::put('/identities/{identity}/accounts/{account_id}',[IdentityController::class,'update_account'])->middleware('can:manage_identity_accounts,App\Models\Identity');
        Route::delete('/identities/{identity}/accounts/{account}',[IdentityController::class,'delete_account'])->middleware('can:manage_identity_accounts,App\Models\Identity');
        Route::put('/identities/{identity}/accounts/{account_id}/restore',[IdentityController::class,'restore_account'])->middleware('can:manage_identity_accounts,App\Models\Identity');

        // Identity Groups
        Route::get('/identities/{identity}/groups',[IdentityController::class,'get_groups'])->middleware('can:view_identity_info,App\Models\Identity');

        // Identity Entitlements
        Route::get('/identities/{identity}/entitlements',[IdentityController::class,'get_entitlements'])->middleware('can:override_identity_entitlements,App\Models\Identity');
        Route::post('/identities/{identity}/entitlements',[IdentityController::class,'add_entitlement'])->middleware('can:override_identity_entitlements,App\Models\Identity');
        Route::put('/identities/{identity}/entitlements/{identity_entitlement}',[IdentityController::class,'update_entitlement'])->middleware('can:override_identity_entitlements,App\Models\Identity');
        Route::post('/entitlements/renew',[IdentityController::class,'renew_entitlements']);//->middleware('can:renew_identity_entitlements,App\Models\Identity');

        // Recalculate
        Route::get('/identities/{identity}/recalculate',[IdentityController::class,'recalculate'])->middleware('can:update_identities,App\Models\Identity');

        //Logs
        Route::get('/identities/{identity}/logs',[LogController::class,'get_identity_logs'])->middleware('can:view,App\Models\Log');

        /* Group Methods */
        Route::get('/groups',[GroupController::class,'get_all_groups'])->middleware('can:list_search,App\Models\Group');
        Route::get('/groups/{group}',[GroupController::class,'get_group'])->middleware('can:manage_groups,App\Models\Group');
        Route::post('/groups',[GroupController::class,'add_group'])->middleware('can:manage_groups,App\Models\Group');
        Route::put('/groups/order',[GroupController::class,'update_groups_order'])->middleware('can:manage_groups,App\Models\Group');
        Route::put('/groups/{group}',[GroupController::class,'update_group'])->middleware('can:manage_groups,App\Models\Group');
        Route::delete('/groups/{group}',[GroupController::class,'delete_group'])->middleware('can:manage_groups,App\Models\Group');
        Route::get('/groups/{group}/members',[GroupController::class,'get_members'])->middleware('can:manage_group_members,group');
        Route::post('/groups/{group}/members',[GroupController::class,'add_member'])->middleware('can:manage_group_members,group');
        Route::delete('/groups/{group}/members/{identity}',[GroupController::class,'delete_member'])->middleware('can:manage_group_members,group');
        Route::get('/groups/{group}/admins',[GroupController::class,'get_admins'])->middleware('can:manage_group_admins,group');
        Route::post('/groups/{group}/admins',[GroupController::class,'add_admin'])->middleware('can:manage_group_admins,group');
        Route::delete('/groups/{group}/admins/{identity}',[GroupController::class,'delete_admin'])->middleware('can:manage_group_admins,group');
        Route::get('/groups/{group}/entitlements',[GroupController::class,'get_entitlements'])->middleware('can:manage_group_entitlements,group');
        Route::post('/groups/{group}/entitlements',[GroupController::class,'add_entitlement'])->middleware('can:manage_group_entitlements,group');
        Route::delete('/groups/{group}/entitlements/{entitlement}',[GroupController::class,'delete_entitlement'])->middleware('can:manage_group_entitlements,group');
        // Route::post('/groups/{group}/identities/{identity}','GroupController@add_group_membership')->middleware('can:manage_group_membership,App\Models\Group');
        // Route::delete('/groups/{group}/identities/{identity}','GroupController@delete_group_membership')->middleware('can:manage_group_membership,App\Models\Group');

        /* Systems Methods */
        Route::get('/systems',[SystemController::class,'get_all_systems'])->middleware('can:list_search,App\Models\System');
        Route::get('/systems/{system}',[SystemController::class,'get_system'])->middleware('can:list_search,App\Models\System');
        Route::post('/systems',[SystemController::class,'add_system'])->middleware('can:manage_systems,App\Models\System');
        Route::put('/systems/{system}',[SystemController::class,'update_system'])->middleware('can:manage_systems,App\Models\System');
        Route::delete('/systems/{system}',[SystemController::class,'delete_system'])->middleware('can:manage_systems,App\Models\System');

        /* Entitlements Methods */
        Route::get('/entitlements',[EntitlementController::class,'get_all_entitlements'])->middleware('can:list_search,App\Models\Entitlement');
        Route::get('/entitlements/{entitlement}',[EntitlementController::class,'get_entitlement'])->middleware('can:manage_entitlements,App\Models\Entitlement');
        Route::post('/entitlements',[EntitlementController::class,'add_entitlement'])->middleware('can:manage_entitlements,App\Models\Entitlement');
        Route::put('/entitlements/{entitlement}',[EntitlementController::class,'update_entitlement'])->middleware('can:manage_entitlements,App\Models\Entitlement');
        Route::delete('/entitlements/{entitlement}',[EntitlementController::class,'delete_entitlement'])->middleware('can:manage_entitlements,App\Models\Entitlement');
        Route::get('/entitlements/{entitlement}/groups',[EntitlementController::class,'get_groups'])->middleware('can:list_search,App\Models\Entitlement');
        Route::post('/entitlements/{entitlement}/groups',[EntitlementController::class,'add_group'])->middleware('can:manage_group_entitlements,App\Models\Group');
        Route::delete('/entitlements/{entitlement}/groups/{group}',[EntitlementController::class,'delete_group'])->middleware('can:manage_entitlements,App\Models\Entitlement');
        Route::get('/entitlements/{entitlement}/overrides',[EntitlementController::class,'get_entitlement_overrides'])->middleware('can:list_search,App\Models\Entitlement');

        /* API Endpoints Methods */
        Route::get('/endpoints',[EndpointController::class,'get_all_endpoints'])->middleware('can:list_search,App\Models\Endpoint');
        Route::get('/endpoints/{endpoint}',[EndpointController::class,'get_endpoint'])->middleware('can:manage_endpoints,App\Models\Endpoint');
        Route::post('/endpoints',[EndpointController::class,'add_endpoint'])->middleware('can:manage_endpoints,App\Models\Endpoint');
        Route::put('/endpoints/{endpoint}',[EndpointController::class,'update_endpoint'])->middleware('can:manage_endpoints,App\Models\Endpoint');
        Route::delete('/endpoints/{endpoint}',[EndpointController::class,'delete_endpoint'])->middleware('can:manage_endpoints,App\Models\Endpoint');

        /* Configuration Methods */
        Route::get('/configuration',[ConfigurationController::class,'get_configurations'])->middleware('can:list_search,App\Models\Configuration');
        Route::get('/configuration/refresh/redis',[ConfigurationController::class, 'refresh_redis'])->middleware('can:flush_job_queue,App\Models\Job');
        Route::put('/configuration/{config_name}',[ConfigurationController::class,'update_configuration'])->middleware('can:update,App\Models\Configuration');

        /* Logs Methods */
        Route::get('/logs',[LogController::class,'get_logs'])->middleware('can:view,App\Models\Log'); // Get All Logs

        Route::get('/group_action_queue',[GroupActionQueueController::class,'get_queue'])->middleware('can:view_in_admin,App\Models\GroupActionQueue');
        Route::post('/group_action_queue/execute',[GroupActionQueueController::class,'execute'])->middleware('can:manage_group_action_queue,App\Models\GroupActionQueue');
    });



});
