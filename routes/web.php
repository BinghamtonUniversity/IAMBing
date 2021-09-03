<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\EntitlementController;

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
Route::group(['middleware'=>['custom.auth']], function () {
    
    Route::get('/', function () {
        return view('welcome');
    });

    /* Admin Pages */
    Route::group(['prefix' => 'admin'], function () {
        Route::get('/', [AdminController::class, 'admin']);
        Route::get('/users', [AdminController::class, 'users']);
        Route::get('/users/{user}/accounts', [AdminController::class, 'user_accounts']);
        Route::get('/users/{user}/permissions', [AdminController::class, 'user_permissions']);
        Route::get('/groups', [AdminController::class, 'groups']);
        Route::get('/groups/{group}/members', [AdminController::class, 'group_members']);
        Route::get('/groups/{group}/admins', [AdminController::class, 'group_admins']);
        Route::get('/groups/{group}/entitlements', [AdminController::class, 'group_entitlements']);
        Route::get('/systems', [AdminController::class, 'systems']);
        Route::get('/entitlements', [AdminController::class, 'entitlements']);
        Route::get('/entitlements/{entitlement}/groups', [AdminController::class, 'entitlement_groups']);
    });

    Route::group(['prefix' => 'api'], function () {
        /* User Methods */
        Route::get('/users','UserController@get_all_users')->middleware('can:view_in_admin,App\Models\User');
        Route::get('/users/search/{search_string?}',[UserController::class,'search']);
        Route::get('/users/{user}',[UserController::class,'get_user']);
        Route::post('/users',[UserController::class,'add_user'])->middleware('can:manage_users,App\Models\User');
        Route::put('/users/{user}',[UserController::class,'update_user'])->middleware('can:manage_users,App\Models\User');
        Route::delete('/users/{user}','UserController@delete_user')->middleware('can:manage_users,App\Models\User');
        Route::put('/users/{source_user}/merge_into/{target_user}','UserController@merge_user')->middleware('can:manage_users,App\Models\User');
        Route::put('/users/{user}/permissions',[UserController::class,'set_permissions'])->middleware('can:manage_user_permissions,App\Models\User');
        Route::get('/users/{user}/permissions',[UserController::class,'get_permissions'])->middleware('can:manage_user_permissions,App\Models\User');
        Route::post('/users/assignments/{module}','UserController@self_assignment');
        Route::get('/users/{user}/assignments','UserController@get_assignments')->middleware('can:manage_users,App\Models\User');
        Route::post('/users/{user}/assignments/{module}','UserController@set_assignment')->middleware('can:assign_module,App\Models\User,module');
        Route::delete('/users/{user}/assignments/{module_assignment}','UserController@delete_assignment')->middleware('can:delete_assignment,App\Models\User,module_assignment');
        Route::post('/login/{user}','UserController@login_user')->middleware('can:impersonate_users,App\Models\User');

        /* Group Methods */
        Route::get('/groups',[GroupController::class,'get_all_groups'])->middleware('can:view_in_admin,App\Models\Group');
        Route::get('/groups/{group}',[GroupController::class,'get_group'])->middleware('can:manage_groups,App\Models\Group');
        Route::post('/groups',[GroupController::class,'add_group'])->middleware('can:manage_groups,App\Models\Group');
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
        Route::delete('/groups/{group}/entitlements/{user}',[GroupController::class,'delete_entitlement'])->middleware('can:manage_groups,App\Models\Group');
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
        Route::delete('/entitlements/{entitlement}/groups/{user}',[EntitlementController::class,'delete_group'])->middleware('can:manage_entitlements,App\Models\Entitlement');


    });

});
