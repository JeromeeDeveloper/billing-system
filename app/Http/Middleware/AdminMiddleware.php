<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && (Auth::user()->role === 'admin' || Auth::user()->role === 'admin-msp')) {
            return $next($request);
        }

        $role = Auth::check() ? Auth::user()->role : null;
        Log::warning('AdminMiddleware blocked', ['authenticated' => Auth::check(), 'role' => $role, 'path' => $request->path()]);
        return redirect()->route('login.form')->with('error', 'You are not authorized to access this page.');
    }
}
