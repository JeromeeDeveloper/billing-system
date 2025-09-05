<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContraController extends Controller
{
    public function showAdmin(Request $request)
    {
        $query = DB::table('contra_acc');
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('account_number', 'like', "%$search%")
                  ->orWhere('loan_acc_no', 'like', "%$search%")
                  ->orWhere('type', 'like', "%$search%") ;
            });
        }
        $contraAccs = $query->orderByDesc('id')->paginate(10)->appends($request->all());
        return view('components.admin.Contra.contra', compact('contraAccs'));
    }

    public function storeAdmin(Request $request)
    {
        return $this->storeContra($request);
    }

    private function storeContra(Request $request)
    {
        $request->validate([
            'type' => 'required|in:shares,savings,loans',
            'account_numbers' => 'required|string',
        ]);

        // Check if a contra entry already exists for this type
        $existingContra = DB::table('contra_acc')->where('type', $request->type)->first();
        if ($existingContra) {
            return back()->with('error', "A contra account for {$request->type} already exists. Only one contra account is allowed per type.");
        }

        // Split comma-separated input, trim whitespace, remove empty
        $accountNumbers = array_filter(array_map('trim', explode(',', $request->account_numbers)));

        foreach ($accountNumbers as $account_number) {
            $data = [
                'type' => $request->type,
                'account_number' => $account_number,
                'loan_acc_no' => null,
                'savings_id' => null,
                'shares_id' => null,
                'loan_forecast_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            // No more lookups for related tables; just store the GL account number
            DB::table('contra_acc')->insert($data);
        }

        return back()->with('success', 'Contra account link(s) created successfully.');
    }

    // AJAX endpoint to get accounts by type
    public function getAccountsByType(Request $request)
    {
        $type = $request->input('type');
        $accounts = [];
        if ($type === 'loans') {
            $accounts = DB::table('loan_forecast')->select('loan_acct_no as value')->distinct()->pluck('value');
        } elseif ($type === 'shares') {
            $accounts = DB::table('shares')->select('account_number as value')->distinct()->pluck('value');
        } elseif ($type === 'savings') {
            $accounts = DB::table('savings')->select('account_number as value')->distinct()->pluck('value');
        }
        return response()->json($accounts);
    }

    // Update contra account (admin)
    public function updateAdmin(Request $request, $id)
    {
        $request->validate([
            'type' => 'required|in:shares,savings,loans',
            'account_number' => 'required|string',
        ]);

        // Check if the type is being changed and if the new type already exists
        $currentContra = DB::table('contra_acc')->where('id', $id)->first();
        if ($currentContra && $currentContra->type !== $request->type) {
            // Type is being changed, check if the new type already exists
            $existingContra = DB::table('contra_acc')->where('type', $request->type)->where('id', '!=', $id)->first();
            if ($existingContra) {
                return back()->with('error', "A contra account for {$request->type} already exists. Only one contra account is allowed per type.");
            }
        }

        DB::table('contra_acc')->where('id', $id)->update([
            'type' => $request->type,
            'account_number' => $request->account_number,
            'updated_at' => now(),
        ]);
        return back()->with('success', 'Contra account updated successfully.');
    }

    // Delete contra account (admin)
    public function deleteAdmin($id)
    {
        DB::table('contra_acc')->where('id', $id)->delete();
        return back()->with('success', 'Contra account deleted successfully.');
    }
}
