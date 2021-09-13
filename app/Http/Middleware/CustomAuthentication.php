<?php 

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Auth;
use App\User;

class CustomAuthentication
{

    protected $auth;
    protected $cas;

    public function __construct(Guard $auth) {
        $this->auth = $auth;
    }

    public function handle($request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect('/login?redirect='.url()->current());
        }
        return $next($request);
    }
}
