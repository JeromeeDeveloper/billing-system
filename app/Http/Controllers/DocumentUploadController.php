<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DocumentUpload;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\LoanForecastImport;
use App\Imports\SavingsImport;
use App\Imports\SharesImport;
use App\Imports\CifImport;
use Exception;

class DocumentUploadController extends Controller
{
    public function store(Request $request)
    {
        ini_set('max_execution_time', 300);

        $request->validate([
            'file'           => 'required|file|mimes:xls,xlsx,csv',
            'savings_file'   => 'nullable|file|mimes:xls,xlsx,csv',
            'shares_file'    => 'nullable|file|mimes:xls,xlsx,csv',
            'cif_file'       => 'nullable|file|mimes:xls,xlsx,csv',
        ]);

        try {
            $billingPeriod = Auth::user()->billing_period ?? null; // get logged-in user's billing period

            $uploadMappings = [
                'file'         => ['type' => 'Installment File', 'import' => LoanForecastImport::class],
                'savings_file' => ['type' => 'Savings', 'import' => SavingsImport::class],
                'shares_file'  => ['type' => 'Shares', 'import' => SharesImport::class],
                'cif_file'     => ['type' => 'CIF', 'import' => CifImport::class],
            ];

            foreach ($uploadMappings as $field => $options) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $newFileName = time() . '-' . $file->getClientOriginalName();
                    $path = $file->storeAs('uploads/documents', $newFileName, 'public');

                    DocumentUpload::create([
                        'document_type' => $options['type'],
                        'filename'      => $newFileName,
                        'filepath'      => $path,
                        'mime_type'     => $file->getClientMimeType(),
                        'uploaded_by'   => Auth::id(),
                        'upload_date'   => now(),
                        'billing_period' => $billingPeriod,
                    ]);

                    if (in_array($options['type'], ['CIF', 'Installment File'])) {
                        $importClass = new $options['import']($billingPeriod);
                    } else {
                        $importClass = new $options['import']();
                    }


                    Excel::import($importClass, $file);
                }
            }

            return redirect()->back()->with('success', 'Files uploaded and data imported successfully!');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function index()
    {
        $user = Auth::user();
        $billingPeriod = $user->billing_period; // e.g. '2025-05'

        $documents = DocumentUpload::where('billing_period', $billingPeriod)->get();

        return view('components.admin.files.file_datatable', compact('documents'));
    }
}
