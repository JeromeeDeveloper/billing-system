<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Branch;
use Carbon\Carbon;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function index()
    {
        return view('auth.users');
    }

   public function login(Request $request)
{
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (Auth::attempt($credentials, $request->filled('remember'))) {
        $request->session()->regenerate();

        $user = Auth::user();

        // Automatically set billing period to current month and year if not already set
        // or if the current billing period is from a previous month
        $currentBillingPeriod = Carbon::now()->format('Y-m') . '-01'; // Format as YYYY-MM-01

        if (!$user->billing_period ||
            (Carbon::parse($user->billing_period)->format('Y-m') !== Carbon::now()->format('Y-m'))) {
            User::where('id', $user->id)->update(['billing_period' => $currentBillingPeriod]);
        }

        if ($user->role === 'admin') {
            return redirect()->route('dashboard')->with('success', 'Welcome, Admin!');
        } elseif ($user->role === 'branch') {
            return redirect()->route('dashboard_branch')->with('success', 'Welcome, Branch!');
        }
    }

    return back()->withErrors([
        'email' => 'Invalid email or password.',
    ])->onlyInput('email');
}


    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'You have been logged out.');
    }

    public function userindex()
    {
        // Fetch all users with related branch data
        $users = User::with('branch')->get();
        $branches = Branch::all();
        return view('auth.users', compact('users', 'branches'));
    }

    public function update(Request $request)
    {
        $user = User::findOrFail($request->id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:8',
            'role' => 'nullable|in:admin,branch',
            'status' => 'nullable|in:pending,approved',
            'branch_id' => 'nullable|exists:branches,id'
        ]);

        $data = $request->only(['name', 'email', 'role', 'status', 'branch_id']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->back()->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request)
    {
        User::destroy($request->id);
        return redirect()->back()->with('success', 'User deleted successfully.');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'role' => 'required|in:admin,branch',
            'status' => 'required|in:pending,approved',
            'branch_id' => 'nullable|exists:branches,id'
        ]);

        $data = $request->only(['name', 'email', 'role', 'status', 'branch_id']);
        $data['password'] = Hash::make($request->password);

        User::create($data);

        return redirect()->back()->with('success', 'User created successfully.');
    }
}
