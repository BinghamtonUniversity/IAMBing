<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Identity;
use App\Models\IdentityUniqueIDs;

class CASController extends Controller {
    public function login(Request $request) {
        if ($request->ajax()) {
            return response('Unauthorized.', 401);
        }
        cas()->authenticate();

        $identity_attributes = cas()->getAttributes();
        $identity = Identity::whereHas('identity_unique_ids', function($q) use ($identity_attributes){
            $q->where('name','bnumber')->where('value',$identity_attributes['UDC_IDENTIFIER']);
        })->first();
        if (is_null($identity)) {
            $identity = new Identity([
                'first_name'=>$identity_attributes['firstname'],
                'last_name'=>$identity_attributes['lastname'],
                'attributes'=>['email'=>$identity_attributes['mail']],
                'ids'=>['bnumber'=>$identity_attributes['UDC_IDENTIFIER']],
            ]);
            $identity->save();    
        }
        Auth::login($identity);
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
