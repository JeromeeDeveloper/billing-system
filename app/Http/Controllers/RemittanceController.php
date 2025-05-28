<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RemittanceController extends Controller
{
    public function index()
    {
        return view('components.admin.remittance.remittance');
    }
}
