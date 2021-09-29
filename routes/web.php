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
    Route::get('/users/{user?}', [AdminController::class, 'users']);
    Route::get('/users/{user}/accounts', [AdminController::class, 'user_accounts']);
    Route::get('/users/{user}/groups', [AdminController::class, 'user_groups']);
    Route::get('/users/{user}/permissions', [AdminController::class, 'user_permissions']);
    Route::get('/users/{user}/entitlements', [AdminController::class, 'user_entitlements']);
    Route::get('/groups', [AdminController::class, 'groups']);
    Route::get('/groups/{group}/members', [AdminController::class, 'group_members']);
    Route::get('/groups/{group}/admins', [AdminController::class, 'group_admins']);
    Route::get('/groups/{group}/entitlements', [AdminController::class, 'group_entitlements']);
    Route::get('/systems', [AdminController::class, 'systems']);
    Route::get('/entitlements', [AdminController::class, 'entitlements']);
    Route::get('/entitlements/{entitlement}/groups', [AdminController::class, 'entitlement_groups']);
    Route::get('/endpoints', [AdminController::class, 'endpoints']);
    Route::get('/configuration', [AdminController::class, 'configuration']);

    Route::group(['prefix' => 'api'], function () {
        /* User Methods */
        Route::get('/users','UserController@get_all_users')->middleware('can:view_in_admin,App\Models\User');
        Route::get('/users/search/{search_string?}',[UserController::class,'search']);
        Route::get('/users/{user}',[UserController::class,'get_user']);
        Route::post('/users',[UserController::class,'add_user'])->middleware('can:manage_users,App\Models\User');
        Route::put('/users/{user}',[UserController::class,'update_user'])->middleware('can:manage_users,App\Models\User');
        Route::delete('/users/{user}',[UserController::class,'delete_user'])->middleware('can:manage_users,App\Models\User');
        Route::put('/users/{user}/permissions',[UserController::class,'set_permissions'])->middleware('can:manage_user_permissions,App\Models\User');
        Route::get('/users/{user}/permissions',[UserController::class,'get_permissions'])->middleware('can:manage_user_permissions,App\Models\User');
        Route::put('/users/{source_user}/merge_into/{target_user}','UserController@merge_user')->middleware('can:manage_users,App\Models\User');
        Route::post('/login/{user}',[UserController::class,'login_user'])->middleware('can:impersonate_users,App\Models\User');
        Route::get('/users/{user}/accounts',[UserController::class,'get_accounts']);
        Route::get('/users/{user}/accounts/{account}',[UserController::class,'get_account']);
        Route::post('/users/{user}/accounts',[UserController::class,'add_account'])->middleware('can:manage_users,App\Models\User');
        Route::delete('/users/{user}/accounts/{account}',[UserController::class,'delete_account'])->middleware('can:manage_users,App\Models\User');
        Route::get('/users/{user}/groups',[UserController::class,'get_groups']);
        Route::get('/users/{user}/entitlements',[UserController::class,'get_entitlements']);
        Route::post('/users/{user}/entitlements',[UserController::class,'add_entitlement']);
        Route::put('/users/{user}/entitlements/{user_entitlement}',[UserController::class,'update_entitlement']);
        Route::get('/users/{user}/recalculate',[UserController::class,'recalculate']);

        /* Group Methods */
        Route::get('/groups',[GroupController::class,'get_all_groups'])->middleware('can:view_in_admin,App\Models\Group');
        Route::get('/groups/{group}',[GroupController::class,'get_group'])->middleware('can:manage_groups,App\Models\Group');
        Route::post('/groups',[GroupController::class,'add_group'])->middleware('can:manage_groups,App\Models\Group');
        Route::put('/groups/order',[GroupController::class,'update_groups_order'])->middleware('can:manage_groups,App\Models\Group');
        Route::put('/groups/{group}',[GroupController::class,'update_group'])->middleware('can:manage_groups,App\Models\Group');
        Route::delete('/groups/{group}',[GroupController::class,'delete_group'])->middleware('can:manage_groups,App\Models\Group');
        Route::get('/groups/{group}/members',[GroupController::class,'get_members'])->middleware('can:manage_groups,App\Models\Group');
        Route::post('/groups/{group}/members',[GroupController::class,'add_member'])->middleware('can:manage_groups,App\Models\Group');
        Route::delete('/groups/{group}/members/{user}',[GroupController::class,'delete_member'])->middleware('can:manage_groups,App\Models\Group');
        Route::get('/groups/{group}/admins',[GroupController::class,'get_admins'])->middleware('can:manage_groups,App\Models\Group');
        Route::post('/groups/{group}/admins',[GroupController::class,'add_admin'])->middleware('can:manage_groups,App\Models\Group');
        Route::delete('/groups/{group}/admins/{user}',[GroupController::class,'delete_admin'])->middleware('can:manage_groups,App\Models\Group');
        Route::get('/groups/{group}/entitlements',[GroupController::class,'get_entitlements'])->middleware('can:manage_groups,App\Models\Group');
        Route::post('/groups/{group}/entitlements',[GroupController::class,'add_entitlement'])->middleware('can:manage_groups,App\Models\Group');
        Route::delete('/groups/{group}/entitlements/{entitlement}',[GroupController::class,'delete_entitlement'])->middleware('can:manage_groups,App\Models\Group');
        // Route::post('/groups/{group}/users/{user}','GroupController@add_group_membership')->middleware('can:manage_group_membership,App\Models\Group');
        // Route::delete('/groups/{group}/users/{user}','GroupController@delete_group_membership')->middleware('can:manage_group_membership,App\Models\Group');

        /* Systems Methods */
        Route::get('/systems',[SystemController::class,'get_all_systems'])->middleware('can:view_in_admin,App\Models\System');
        Route::get('/systems/{system}',[SystemController::class,'get_system'])->middleware('can:manage_systems,App\Models\System');
        Route::post('/systems',[SystemController::class,'add_system'])->middleware('can:manage_systems,App\Models\System');
        Route::put('/systems/{system}',[SystemController::class,'update_system'])->middleware('can:manage_systems,App\Models\System');
        Route::delete('/systems/{system}',[SystemController::class,'delete_system'])->middleware('can:manage_systems,App\Models\System');

        /* Entitlements Methods */
        Route::get('/entitlements',[EntitlementController::class,'get_all_entitlements'])->middleware('can:view_in_admin,App\Models\Entitlement');
        Route::get('/entitlements/{entitlement}',[EntitlementController::class,'get_entitlement'])->middleware('can:manage_entitlements,App\Models\Entitlement');
        Route::post('/entitlements',[EntitlementController::class,'add_entitlement'])->middleware('can:manage_entitlements,App\Models\Entitlement');
        Route::put('/entitlements/{entitlement}',[EntitlementController::class,'update_entitlement'])->middleware('can:manage_entitlements,App\Models\Entitlement');
        Route::delete('/entitlements/{entitlement}',[EntitlementController::class,'delete_entitlement'])->middleware('can:manage_entitlements,App\Models\Entitlement');
        Route::get('/entitlements/{entitlement}/groups',[EntitlementController::class,'get_groups'])->middleware('can:manage_entitlements,App\Models\Entitlement');
        Route::post('/entitlements/{entitlement}/groups',[EntitlementController::class,'add_group'])->middleware('can:manage_entitlements,App\Models\Entitlement');
        Route::delete('/entitlements/{entitlement}/groups/{group}',[EntitlementController::class,'delete_group'])->middleware('can:manage_entitlements,App\Models\Entitlement');

        /* Systems Methods */
        Route::get('/endpoints',[EndpointController::class,'get_all_endpoints'])->middleware('can:view_in_admin,App\Models\Endpoint');
        Route::get('/endpoints/{endpoint}',[EndpointController::class,'get_endpoint'])->middleware('can:manage_endpoints,App\Models\Endpoint');
        Route::post('/endpoints',[EndpointController::class,'add_endpoint'])->middleware('can:manage_endpoints,App\Models\Endpoint');
        Route::put('/endpoints/{endpoint}',[EndpointController::class,'update_endpoint'])->middleware('can:manage_endpoints,App\Models\Endpoint');
        Route::delete('/endpoints/{endpoint}',[EndpointController::class,'delete_endpoint'])->middleware('can:manage_endpoints,App\Models\Endpoint');

        /* Configuration Methods */
        Route::get('/configuration',[ConfigurationController::class,'get_configurations'])->middleware('can:manage_configuration,App\Models\Configuration');
        Route::get('/configuration/refresh/db',[ConfigurationController::class, 'refresh_db'])->middleware('can:manage_configuration,App\Models\Configuration');
        Route::get('/configuration/refresh/redis',[ConfigurationController::class, 'refresh_redis'])->middleware('can:manage_configuration,App\Models\Configuration');
        Route::put('/configuration/{config_name}',[ConfigurationController::class,'update_configuration'])->middleware('can:manage_configuration,App\Models\Configuration');
    });

});
