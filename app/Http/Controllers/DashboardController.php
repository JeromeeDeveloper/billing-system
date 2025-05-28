<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
   public function index(Request $request)
{
    // Check if billing_period prompt was shown this session
    if (!$request->session()->has('billing_prompt_shown')) {
        $request->session()->put('billing_prompt_shown', true);
        $showPrompt = true;
    } else {
        $showPrompt = false;
    }

    return view('components.admin.dashboard.dashboard', compact('showPrompt'));
}


    public function store(Request $request)
    {
        $request->validate([
            'billing_period' => ['required', 'date_format:Y-m'],
        ]);

        $user = Auth::user();
        $user->billing_period = $request->billing_period . '-01'; // Save as full date
        $user->save();

        return response()->json(['message' => 'Billing period saved.']);
    }
}
