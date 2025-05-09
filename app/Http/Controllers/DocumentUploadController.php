<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DocumentUpload;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;


class DocumentUploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,docx,jpg,jpeg,png,xls,xlsx|max:5120',
        ]);

        $file = $request->file('file');
        $path = $file->store('uploads/documents', 'public');

        $upload = DocumentUpload::create([
            'filename'     => $file->getClientOriginalName(),
            'filepath'     => $path,
            'mime_type'    => $file->getClientMimeType(),
            'uploaded_by'  => Auth::id(),
            'upload_date'  => now(),
        ]);

        return redirect()->back()->with('success', 'File uploaded successfully!');
    }
}
