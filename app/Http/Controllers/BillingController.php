<?php

namespace App\Http\Controllers;

use App\Exports\BillingExport;
use App\Models\DocumentUpload;
use Illuminate\Routing\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function index()
    {
        $documents = DocumentUpload::all();

        return view('components.admin.billing.billing', compact('documents'));
    }

    public function export(Request $request)
    {
        $billingPeriod = $request->input('billing_period', now()->format('Y-m')); // default to current month

        return Excel::download(new BillingExport($billingPeriod), 'billing_export.xlsx');
    }
}
