<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;

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
// Route::get('/users/{unique_id}/assignments','PublicAPIController@get_user_assignments');
// Route::get('/modules/{module}/assignments','PublicAPIController@get_module_assignments');

Route::get('/db/refresh',[AdminController::class, 'refresh_db']);