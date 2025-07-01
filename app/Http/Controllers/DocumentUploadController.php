<?php

namespace App\Http\Controllers;

use Exception;
use App\Imports\CifImport;
use App\Imports\LoanImport;
use Illuminate\Http\Request;
use App\Imports\SharesImport;
use App\Imports\SavingsImport;
use App\Models\DocumentUpload;
use App\Imports\LoanForecastImport;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\NotificationController;
use Illuminate\Database\Eloquent\Collection;

class DocumentUploadController extends Controller
{
   public function store(Request $request)
{
    ini_set('max_execution_time', 8000);
    ini_set('memory_limit', '1G'); // Increase memory limit to 1GB

    $request->validate([
        'file'           => 'nullable|file',
        'savings_file'   => 'nullable|file',
        'shares_file'    => 'nullable|file',
        'cif_file'       => 'nullable|file',
        'loan_file'      => 'nullable|file',
    ]);

    try {
        $billingPeriod = Auth::user()->billing_period ?? null;

        $uploadMappings = [
            'file'         => ['type' => 'Installment File', 'import' => LoanForecastImport::class],
            'savings_file' => ['type' => 'Savings', 'import' => SavingsImport::class],
            'shares_file'  => ['type' => 'Shares', 'import' => SharesImport::class],
            'cif_file'     => ['type' => 'CIF', 'import' => CifImport::class],
            'loan_file'    => ['type' => 'Loan', 'import' => LoanImport::class],
        ];

        foreach ($uploadMappings as $field => $options) {
            if ($request->hasFile($field)) {
                // Delete existing files for this document_type & billing_period
                $existingFiles = DocumentUpload::where('document_type', $options['type'])
                    ->where('billing_period', $billingPeriod)
                    ->get();

                foreach ($existingFiles as $existingFile) {
                    if (Storage::disk('public')->exists($existingFile->filepath)) {
                        Storage::disk('public')->delete($existingFile->filepath);
                    }
                    $existingFile->delete();
                }

                $file = $request->file($field);
                $newFileName = time() . '-' . $file->getClientOriginalName();
                $path = $file->storeAs('uploads/documents', $newFileName, 'public');

                $documentUpload = DocumentUpload::create([
                    'document_type'  => $options['type'],
                    'filename'       => $newFileName,
                    'filepath'       => $path,
                    'mime_type'      => $file->getClientMimeType(),
                    'uploaded_by'    => Auth::id(),
                    'upload_date'    => now(),
                    'billing_period' => $billingPeriod,
                ]);

                // Add notification
                NotificationController::createNotification('document_upload', Auth::id(), $documentUpload->id);

                if (in_array($options['type'], ['CIF', 'Installment File', 'Savings', 'Shares', 'Loan'])) {
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

 public function store_branch(Request $request)
{
    ini_set('max_execution_time', 600);
    ini_set('memory_limit', '1G'); // Increase memory limit to 1GB

    $request->validate([
        'file'           => 'nullable|file',
        'savings_file'   => 'nullable|file',
        'shares_file'    => 'nullable|file',
        'cif_file'       => 'nullable|file',
        'loan_file'      => 'nullable|file',
    ]);

    try {
        $billingPeriod = Auth::user()->billing_period ?? null;

        $uploadMappings = [
            'cif_file'     => ['type' => 'CIF', 'import' => CifImport::class],
            'file'         => ['type' => 'Installment File', 'import' => LoanForecastImport::class],
            'savings_file' => ['type' => 'Savings', 'import' => SavingsImport::class],
            'shares_file'  => ['type' => 'Shares', 'import' => SharesImport::class],
            'loan_file'    => ['type' => 'Loan', 'import' => LoanImport::class],
        ];

        foreach ($uploadMappings as $field => $options) {
            if ($request->hasFile($field)) {
                // Delete existing files for this document_type & billing_period
                $existingFiles = DocumentUpload::where('document_type', $options['type'])
                    ->where('billing_period', $billingPeriod)
                    ->get();

                foreach ($existingFiles as $existingFile) {
                    if (Storage::disk('public')->exists($existingFile->filepath)) {
                        Storage::disk('public')->delete($existingFile->filepath);
                    }
                    $existingFile->delete();
                }

                $file = $request->file($field);
                $newFileName = time() . '-' . $file->getClientOriginalName();
                $path = $file->storeAs('uploads/documents', $newFileName, 'public');

                $documentUpload = DocumentUpload::create([
                    'document_type'  => $options['type'],
                    'filename'       => $newFileName,
                    'filepath'       => $path,
                    'mime_type'      => $file->getClientMimeType(),
                    'uploaded_by'    => Auth::id(),
                    'upload_date'    => now(),
                    'billing_period' => $billingPeriod,
                ]);

                // Add notification
                NotificationController::createNotification('document_upload', Auth::id(), $documentUpload->id);

                if (in_array($options['type'], ['CIF', 'Installment File', 'Savings', 'Shares', 'Loan'])) {
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

    public function index_branch()
    {
        $user = Auth::user();
        $billingPeriod = $user->billing_period; // e.g. '2025-05'

        $documents = DocumentUpload::where('billing_period', $billingPeriod)->get();

        return view('components.branch.files.file_datatable', compact('documents'));
    }
}
