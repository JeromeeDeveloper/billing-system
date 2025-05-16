<?php

namespace App\Http\Controllers;
use App\Models\MasterList;
use Illuminate\Http\Request;

class MasterController extends Controller
{
     public function index()
    {

        $masterlists = MasterList::with(['member', 'branch'])->get();

        return view('components.master.master', compact('masterlists'));
    }
}
