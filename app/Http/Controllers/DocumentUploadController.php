<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DocumentUpload;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\LoanForecastImport;
use Exception;

class DocumentUploadController extends Controller
{
    public function store(Request $request)
    {
        ini_set('max_execution_time', 300);

        $request->validate([
            'file' => 'required|file|mimes:xls,xlsx,csv|max:5120',
        ]);

        try {
            $file = $request->file('file');

            // Generate a new file name based on your custom logic
            $newFileName = time() . '-' . $file->getClientOriginalName(); // Example: timestamp + original name

            // Store the file with the new name
            $path = $file->storeAs('uploads/documents', $newFileName, 'public');

            // Try to import the data
            Excel::import(new LoanForecastImport, $file);

            // Log upload
            DocumentUpload::create([
                'filename'     => $newFileName,  // Use the new file name
                'filepath'     => $path,
                'mime_type'    => $file->getClientMimeType(),
                'uploaded_by'  => Auth::id(),
                'upload_date'  => now(),
            ]);

            return redirect()->back()->with('success', 'File uploaded and data imported successfully!');
        } catch (Exception $e) {
            // Return error message for debugging
            return redirect()->back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
