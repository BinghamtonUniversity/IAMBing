<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\EntitlementController;
use App\Http\Controllers\EndpointController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\CASController;

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
    Route::get('/users/{user?}', [AdminController::class, 'users'])->middleware('can:view_in_admin,App\Models\User');
    Route::get('/users/{user}/accounts', [AdminController::class, 'user_accounts'])->middleware('can:override_user_accounts,App\Models\User');
    Route::get('/users/{user}/groups', [AdminController::class, 'user_groups'])->middleware('can:manage_groups,App\Models\Group');
    Route::get('/users/{user}/permissions', [AdminController::class, 'user_permissions'])->middleware('can:manage_user_permissions,App\Models\User');
    Route::get('/users/{user}/entitlements', [AdminController::class, 'user_entitlements'])->middleware('can:override_user_entitlements,App\Models\User');
    Route::get('/groups', [AdminController::class, 'groups'])->middleware('can:list_search,App\Models\Group');
    Route::get('/groups/{group}/members', [AdminController::class, 'group_members'])->middleware('can:manage_group_members,group');
    Route::get('/groups/{group}/admins', [AdminController::class, 'group_admins'])->middleware('can:manage_groups,App\Models\Group');
    Route::get('/groups/{group}/entitlements', [AdminController::class, 'group_entitlements'])->middleware('can:manage_group_entitlements,group');
    Route::get('/systems', [AdminController::class, 'systems'])->middleware('can:list_search,App\Models\System');
    Route::get('/entitlements', [AdminController::class, 'entitlements'])->middleware('can:list_search,App\Models\Entitlement');
    Route::get('/entitlements/{entitlement}/groups', [AdminController::class, 'entitlement_groups'])->middleware('can:manage_group_entitlements,App\Models\Group');
    Route::get('/endpoints', [AdminController::class, 'endpoints'])->middleware('can:list_search,App\Models\Endpoint');
    Route::get('/configuration', [AdminController::class, 'configuration'])->middleware('can:list_search,App\Models\Configuration');

    Route::group(['prefix' => 'api'], function () {
        /* User Methods */
        Route::get('/users','UserController@get_all_users')->middleware('can:view_in_admin,App\Models\User');
        Route::get('/users/search/{search_string?}',[UserController::class,'search'])->middleware('can:list_search,App\Models\User');
        Route::get('/users/{user}',[UserController::class,'get_user'])->middleware('can:view_user_info,App\Models\User');
        Route::post('/users',[UserController::class,'add_user'])->middleware('can:add_users,App\Models\User');
        Route::put('/users/{user}',[UserController::class,'update_user'])->middleware('can:update_users,App\Models\User');
        Route::delete('/users/{user}',[UserController::class,'delete_user'])->middleware('can:delete_users,App\Models\User');
        //User Permissions
        Route::put('/users/{user}/permissions',[UserController::class,'set_permissions'])->middleware('can:manage_user_permissions,App\Models\User');
        Route::get('/users/{user}/permissions',[UserController::class,'get_permissions'])->middleware('can:manage_user_permissions,App\Models\User');
        //Merge User
        Route::put('/users/{source_user}/merge_into/{target_user}','UserController@merge_user')->middleware('can:merge_users,App\Models\User');
        //Impersonate
        Route::post('/login/{user}',[UserController::class,'login_user'])->middleware('can:impersonate_users,App\Models\User');

        // User Accounts
        Route::get('/users/{user}/accounts',[UserController::class,'get_accounts'])->middleware('can:view_user_info,App\Models\User');
        Route::get('/users/{user}/accounts/{account}',[UserController::class,'get_account'])->middleware('can:view_user_info,App\Models\User');
        Route::post('/users/{user}/accounts',[UserController::class,'add_account'])->middleware('can:override_user_accounts,App\Models\User');
        Route::delete('/users/{user}/accounts/{account}',[UserController::class,'delete_account'])->middleware('can:override_user_accounts,App\Models\User');
        Route::put('/users/{user}/accounts/{account}',[UserController::class,'update_account'])->middleware('can:override_user_accounts,App\Models\User');

        // User Groups
        Route::get('/users/{user}/groups',[UserController::class,'get_groups'])->middleware('can:manage_groups,App\Models\Group');

        // User Entitlements
        Route::get('/users/{user}/entitlements',[UserController::class,'get_entitlements'])->middleware('can:override_user_entitlements,App\Models\User');
        Route::post('/users/{user}/entitlements',[UserController::class,'add_entitlement'])->middleware('can:override_user_entitlements,App\Models\User');
        Route::put('/users/{user}/entitlements/{user_entitlement}',[UserController::class,'update_entitlement'])->middleware('can:override_user_entitlements,App\Models\User');

        // Recalculate
        Route::get('/users/{user}/recalculate',[UserController::class,'recalculate'])->middleware('can:manage_users,App\Models\User');

        /* Group Methods */
        Route::get('/groups',[GroupController::class,'get_all_groups'])->middleware('can:list_search,App\Models\Group');
        Route::get('/groups/{group}',[GroupController::class,'get_group'])->middleware('can:manage_groups,App\Models\Group');
        Route::post('/groups',[GroupController::class,'add_group'])->middleware('can:manage_groups,App\Models\Group');
        Route::put('/groups/order',[GroupController::class,'update_groups_order'])->middleware('can:manage_groups,App\Models\Group');
        Route::put('/groups/{group}',[GroupController::class,'update_group'])->middleware('can:manage_groups,App\Models\Group');
        Route::delete('/groups/{group}',[GroupController::class,'delete_group'])->middleware('can:manage_groups,App\Models\Group');
        Route::get('/groups/{group}/members',[GroupController::class,'get_members'])->middleware('can:manage_group_members,group');
        Route::post('/groups/{group}/members',[GroupController::class,'add_member'])->middleware('can:manage_group_members,group');
        Route::delete('/groups/{group}/members/{user}',[GroupController::class,'delete_member'])->middleware('can:manage_group_members,group');
        Route::get('/groups/{group}/admins',[GroupController::class,'get_admins'])->middleware('can:manage_group_admins,group');
        Route::post('/groups/{group}/admins',[GroupController::class,'add_admin'])->middleware('can:manage_group_admins,group');
        Route::delete('/groups/{group}/admins/{user}',[GroupController::class,'delete_admin'])->middleware('can:manage_group_admins,group');
        Route::get('/groups/{group}/entitlements',[GroupController::class,'get_entitlements'])->middleware('can:manage_group_entitlements,group');
        Route::post('/groups/{group}/entitlements',[GroupController::class,'add_entitlement'])->middleware('can:manage_group_entitlements,group');
        Route::delete('/groups/{group}/entitlements/{entitlement}',[GroupController::class,'delete_entitlement'])->middleware('can:manage_group_entitlements,group');
        // Route::post('/groups/{group}/users/{user}','GroupController@add_group_membership')->middleware('can:manage_group_membership,App\Models\Group');
        // Route::delete('/groups/{group}/users/{user}','GroupController@delete_group_membership')->middleware('can:manage_group_membership,App\Models\Group');

        /* Systems Methods */
        Route::get('/systems',[SystemController::class,'get_all_systems'])->middleware('can:list_search,App\Models\System');
        Route::get('/systems/{system}',[SystemController::class,'get_system'])->middleware('can:manage_systems,App\Models\System');
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

        /* API Endpoints Methods */
        Route::get('/endpoints',[EndpointController::class,'get_all_endpoints'])->middleware('can:list_search,App\Models\Endpoint');
        Route::get('/endpoints/{endpoint}',[EndpointController::class,'get_endpoint'])->middleware('can:manage_endpoints,App\Models\Endpoint');
        Route::post('/endpoints',[EndpointController::class,'add_endpoint'])->middleware('can:manage_endpoints,App\Models\Endpoint');
        Route::put('/endpoints/{endpoint}',[EndpointController::class,'update_endpoint'])->middleware('can:manage_endpoints,App\Models\Endpoint');
        Route::delete('/endpoints/{endpoint}',[EndpointController::class,'delete_endpoint'])->middleware('can:manage_endpoints,App\Models\Endpoint');

        /* Configuration Methods */
        Route::get('/configuration',[ConfigurationController::class,'get_configurations'])->middleware('can:list_search,App\Models\Configuration');
        Route::get('/configuration/refresh/db',[ConfigurationController::class, 'refresh_db'])->middleware('can:list_search,App\Models\Configuration');
        Route::get('/configuration/refresh/redis',[ConfigurationController::class, 'refresh_redis'])->middleware('can:list_search,App\Models\Configuration');
        Route::put('/configuration/{config_name}',[ConfigurationController::class,'update_configuration'])->middleware('can:update,App\Models\Configuration');
    });

});
