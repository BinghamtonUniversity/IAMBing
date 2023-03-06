<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\PublicAPIController;

// All Public API Routes are prepended by /api/public
// as per the RouteServiceProvider Controller
//
// You must authenticate with a valid username / password
// as specified by: API_USER and API_PASS 
// in your .env file

Route::get('/db/refresh',[ConfigurationController::class, 'refresh_db']);

// Groups method
Route::get('/groups',[PublicAPIController::class,'get_all_groups']);
Route::get('/groups/{group_slug}',[PublicAPIController::class,'get_group']);
Route::post('/groups',[PublicAPIController::class,'add_group']);
Route::put('/groups/{group_slug}',[PublicAPIController::class,'update_group']);
Route::delete('/groups/{group_slug}',[PublicAPIController::class,'delete_group']);
Route::post('/groups/{group_slug}/entitlements/{entitlement}',[GroupController::class,'add_entitlement_to_group']);
Route::delete('/groups/{group_slug}/entitlements/{entitlement}',[GroupController::class,'delete_entitlement_from_group']);

Route::post('/groups/{group_slug}/member',[PublicAPIController::class,'insert_group_member']); 
Route::delete('/groups/{group_slug}/member',[PublicAPIController::class,'remove_group_member']); 
Route::post('/groups/{group_slug}/members',[PublicAPIController::class, 'bulk_update_group_members']);
Route::post('/groups/{group_slug}/admin',[PublicAPIController::class,'insert_group_admin']); 
Route::delete('/groups/{group_slug}/admin',[PublicAPIController::class,'remove_group_admin']); 

// Identities
Route::get('/identities/{unique_id_type}/{unique_id}/entitlements',[PublicAPIController::class,'get_identity_entitlements']);
Route::put('/identities/{unique_id_type}/{unique_id}/entitlements/{entitlement_name}',[PublicAPIController::class,'update_identity_entitlement']);
Route::get('/identities/search/{search}',[PublicAPIController::class,'identity_search']);
Route::get('/identities/{unique_id_type}/{unique_id}',[PublicAPIController::class,'get_identity']);
Route::post('/identities',[PublicAPIController::class, 'insert_update_identity']);
Route::post('/identities/bulk_update',[PublicAPIController::class, 'bulk_update_identities']);



// Entitlements 
Route::get('/entitlements',[PublicAPIController::class,'get_all_entitlements']);
Route::get('/entitlements/{entitlement}',[PublicAPIController::class,'get_entitlement']);
Route::post('/entitlements',[PublicAPIController::class,'add_entitlement']);
Route::put('/entitlements/{entitlement}',[PublicAPIController::class,'update_entitlement']);
Route::delete('/entitlements/{entitlement}',[PublicAPIController::class,'delete_entitlement']);
Route::post('/entitlements/{entitlement}/groups/{group_slug}',[PublicAPIController::class,'add_group_to_entitlement']);
Route::delete('/entitlements/{entitlement}/groups/{group_slug}',[PublicAPIController::class,'delete_group_from_entitlement']);
