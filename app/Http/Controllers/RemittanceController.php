<?php

namespace App\Http\Controllers;

use App\Imports\RemittanceImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class RemittanceController extends Controller
{
    public function index()
    {
        return view('components.admin.remittance.remittance');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:10240', // max 10MB
        ]);

        try {
            $import = new RemittanceImport();
            Excel::import($import, $request->file('file'));

            return redirect()->back()
                ->with('preview', $import->getResults())
                ->with('stats', $import->getStats())
                ->with('success', 'File processed successfully. Check the preview below.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error processing file: ' . $e->getMessage());
        }
    }
}
