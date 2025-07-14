<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContraController extends Controller
{
    public function showAdmin()
    {
        return view('components.admin.Contra.contra');
    }

    public function storeAdmin(Request $request)
    {
        return $this->storeContra($request);
    }

    private function storeContra(Request $request)
    {
        $request->validate([
            'type' => 'required|in:shares,savings,loans',
            'account_numbers' => 'required|array|min:1',
            'account_numbers.*' => 'required|string',
        ]);

        foreach ($request->account_numbers as $account_number) {
            $data = [
                'type' => $request->type,
                'account_number' => null,
                'loan_acc_no' => null,
                'savings_id' => null,
                'shares_id' => null,
                'loan_forecast_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if ($request->type === 'loans') {
                $data['loan_acc_no'] = $account_number;
                $loan = DB::table('loan_forecast')->where('loan_acct_no', $account_number)->first();
                if ($loan) {
                    $data['loan_forecast_id'] = $loan->id;
                }
            } else if ($request->type === 'shares') {
                $data['account_number'] = $account_number;
                $share = DB::table('shares')->where('account_number', $account_number)->first();
                if ($share) {
                    $data['shares_id'] = $share->id;
                }
            } else if ($request->type === 'savings') {
                $data['account_number'] = $account_number;
                $saving = DB::table('savings')->where('account_number', $account_number)->first();
                if ($saving) {
                    $data['savings_id'] = $saving->id;
                }
            }

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
}
