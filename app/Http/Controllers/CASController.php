<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\UserUniqueIDs;

class CASController extends Controller {
    public function login(Request $request) {
        if(!Auth::check() && !cas()->checkAuthentication()) {
            if ($request->ajax()) {
                return response('Unauthorized.', 401);
            }
            cas()->authenticate();
        }
        $user_attributes = cas()->getAttributes();
        $user = User::whereHas('user_unique_ids', function($q) use ($user_attributes){
            $q->where('name','bnumber')->where('value',$user_attributes['UDC_IDENTIFIER']);
         })->first();
        if (is_null($user)) {
            $user = new User([
                'first_name'=>$user_attributes['firstname'],
                'last_name'=>$user_attributes['lastname'],
                'attributes'=>['email'=>$user_attributes['mail']],
                'ids'=>['bnumber'=>$user_attributes['UDC_IDENTIFIER']],
            ]);
            $user->save();    
        }
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
