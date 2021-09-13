<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class CASController extends Controller {
    public function login(Request $request) {
        if(!Auth::check() && !cas()->checkAuthentication()) {
            if ($request->ajax()) {
                return response('Unauthorized.', 401);
            }
            cas()->authenticate();
        }
        $user_attributes = cas()->getAttributes();  
        $user = User::where('unique_id',$user_attributes['UDC_IDENTIFIER'])->first();
        if (is_null($user)) {
            $user = new User();
        }
        $user->unique_id = $user_attributes['UDC_IDENTIFIER'];
        $user->first_name = $user_attributes['firstname'];
        $user->last_name = $user_attributes['lastname'];
        $user->save();
        Auth::login($user,true);
        if ($request->has('redirect')) {
            return redirect($request->redirect);
        } else {
            return redirect('/');
        }
    }

    public function logout(Request $request){
        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            if (cas()->checkAuthentication()) {
                cas()->logout();
            }
        } else {
            return response('You are not logged in.', 401);
        }
    }
}
