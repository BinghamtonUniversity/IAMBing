<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\PublicAPIController;
use App\Http\Controllers\IdentityController;

// All Public API Routes are prepended by /api/public
// as per the RouteServiceProvider Controller
//
// You must authenticate with a valid username / password
// as specified by: API_USER and API_PASS 
// in your .env file


Route::get('/db/refresh',[ConfigurationController::class, 'refresh_db']);

// Groups method
Route::post('/groups/{name}/member',[PublicAPIController::class,'insert_group_member']); 
Route::delete('/groups/{name}/member',[PublicAPIController::class,'remove_group_member']); 
Route::post('/groups/{name}/members',[PublicAPIController::class, 'bulk_update_group_members']);

// Identities
Route::get('/identities/{unique_id_type}/{unique_id}',[PublicAPIController::class,'get_identity']);
Route::post('/identities',[PublicAPIController::class, 'insert_update_identity']);
Route::post('/identities/bulk_update',[PublicAPIController::class, 'bulk_update_identities']);