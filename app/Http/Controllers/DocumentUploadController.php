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

        // Validate input
        $request->validate([
            'file'           => 'required|file|mimes:xls,xlsx,csv|max:5120',
            'savings_file'   => 'nullable|file|mimes:xls,xlsx,csv|max:5120',
            'shares_file'    => 'nullable|file|mimes:xls,xlsx,csv|max:5120',
            'cif_file'       => 'nullable|file|mimes:xls,xlsx,csv|max:5120',
        ]);

        try {
            // File field mappings
            $uploadMappings = [
                'file'         => ['type' => 'Installment File', 'import' => LoanForecastImport::class],
                'savings_file' => ['type' => 'Savings', 'import' => SavingsImport::class],
                'shares_file'  => ['type' => 'Shares', 'import' => SharesImport::class],
                'cif_file'     => ['type' => 'CIF', 'import' => CifImport::class],
            ];

            foreach ($uploadMappings as $field => $options) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);

                    // Rename file with timestamp prefix to avoid name collisions
                    $newFileName = time() . '-' . $file->getClientOriginalName();

                    // Store the file
                    $path = $file->storeAs('uploads/documents', $newFileName, 'public');

                    // Save document info to database
                    DocumentUpload::create([
                        'document_type' => $options['type'],
                        'filename'      => $newFileName,
                        'filepath'      => $path,
                        'mime_type'     => $file->getClientMimeType(),
                        'uploaded_by'   => Auth::id(),
                        'upload_date'   => now(),
                    ]);

                    // Import the file's data
                    $importClass = new ($options['import']);
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
        $documents = DocumentUpload::all();
        return view('components.files.file_datatable', compact('documents'));
    }
}
