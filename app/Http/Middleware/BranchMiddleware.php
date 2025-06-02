<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BranchMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Auth::user()->role === 'branch') {
            return $next($request);
        }

        return redirect()->route('login.form')->with('error', 'You are not authorized to access this page.');
    }
}
