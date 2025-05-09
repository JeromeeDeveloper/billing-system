<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AtmController extends Controller
{
    public function index()
    {
        return view('components.atm.atm');
    }
}
