<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CheckBillingPeriod
{
    public function handle($request, Closure $next)
    {
        Log::info('CheckBillingPeriod middleware running', [
            'user_id' => Auth::id(),
            'route' => $request->path(),
        ]);

        if (Auth::check()) {
            $currentPeriod = Carbon::now()->format('Y-m') . '-01';
            // Direct DB query to avoid Eloquent cache
            $dbPeriod = DB::table('users')->where('id', Auth::id())->value('billing_period');

            Log::info('CheckBillingPeriod values', [
                'user_id' => Auth::id(),
                'db_billing_period' => $dbPeriod,
                'currentPeriod' => $currentPeriod,
            ]);

            if ($dbPeriod !== $currentPeriod) {
                Log::info('CheckBillingPeriod: Logging out user due to billing period mismatch', [
                    'user_id' => Auth::id(),
                    'db_billing_period' => $dbPeriod,
                    'currentPeriod' => $currentPeriod,
                ]);
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect('/')->withErrors(['email' => 'Session expired due to new billing period. Please log in again.']);
            }
        }
        return $next($request);
    }
}
