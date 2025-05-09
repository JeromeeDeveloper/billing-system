<?php

namespace App\Http\Controllers;

use App\Models\DocumentUpload;

class BillingController extends Controller
{
    public function index()
    {
        $documents = DocumentUpload::all();

        return view('components.billing.billing', compact('documents'));
    }
}
