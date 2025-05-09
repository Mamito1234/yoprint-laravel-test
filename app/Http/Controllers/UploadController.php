<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Jobs\ProcessCsvUpload;

class UploadController extends Controller
{
    public function index()
    {
        $uploads = Upload::orderByDesc('created_at')->get();
        return view('uploads.index', compact('uploads'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        // Save file to storage
        $file = $request->file('csv_file');
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('private/uploads', $filename);

        // Create upload record
        $upload = Upload::create([
            'filename' => $filename,
            'status' => 'pending',
        ]);

        // Dispatch background job
        ProcessCsvUpload::dispatch($upload);

        return redirect()->route('uploads.index');
    }
}
