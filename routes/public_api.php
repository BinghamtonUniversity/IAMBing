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

// Route::any('/sync','PublicAPIController@sync');
// Route::get('/cron', function () {
//     $exitCode = Artisan::call('schedule:run');
//     return ['code'=>$exitCode];
// });
// Route::get('/identities/{unique_id}/assignments','PublicAPIController@get_identity_assignments');
// Route::get('/modules/{module}/assignments','PublicAPIController@get_module_assignments');

Route::get('/db/refresh',[ConfigurationController::class, 'refresh_db']);

// Groups method
Route::post('/groups/{name}/members',[PublicAPIController::class, 'bulk_update_group_members']);

Route::post('/groups/{name}/member',[PublicAPIController::class,'insert_group_member']); 

//The code below needs to be updated when there is a new Graphene update for the search attribute of the combobox fields
// The search attribute of the combobox field needs to be able to use the resources
Route::get('/identities/{identity}',[IdentityController::class,'get_identity']);
Route::get('/identities/search/{search_string?}/{groups?}',[PublicAPIController::class,'public_search']); 
Route::post('/identities',[PublicAPIController::class, 'insert_update_identities']);

