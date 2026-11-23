<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\SchoolComplianceDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SchoolComplianceDocumentController extends Controller
{
    private const CATEGORIES = [
        'recognition', 'board_affiliation', 'fire_safety', 'building_safety',
        'land_or_lease', 'society_or_trust', 'sanitation', 'other',
    ];

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', 'string', 'in:'.implode(',', self::CATEGORIES)],
            'title' => 'required|string|max:180',
            'document_number' => 'nullable|string|max:120',
            'issuing_authority' => 'nullable|string|max:180',
            'issued_on' => 'nullable|date',
            'expires_on' => ['nullable', 'date', function ($attribute, $value, $fail) use ($request) {
                if ($value && $request->filled('issued_on') && $value < $request->input('issued_on')) {
                    $fail('The expiry date must be on or after the issue date.');
                }
            }],
            'notes' => 'nullable|string|max:3000',
            'document_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $storedPath = null;
        if ($request->hasFile('document_file')) {
            $file = $request->file('document_file');
            $storedPath = $file->store('school-compliance', 'local');
            abort_if(! $storedPath, 500, 'The document could not be stored. Please retry.');
            $data['file_path'] = $storedPath;
            $data['original_filename'] = mb_substr($file->getClientOriginalName(), 0, 255);
        }
        unset($data['document_file']);
        $data['created_by'] = $request->user()->id;

        try {
            SchoolComplianceDocument::create($data);
        } catch (\Throwable $exception) {
            if ($storedPath) {
                Storage::disk('local')->delete($storedPath);
            }
            throw $exception;
        }

        return back()->with('success', 'School compliance document saved.');
    }

    public function update(Request $request, SchoolComplianceDocument $document): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', 'string', 'in:'.implode(',', self::CATEGORIES)],
            'title' => 'required|string|max:180',
            'document_number' => 'nullable|string|max:120',
            'issuing_authority' => 'nullable|string|max:180',
            'issued_on' => 'nullable|date',
            'expires_on' => ['nullable', 'date', function ($attribute, $value, $fail) use ($request) {
                if ($value && $request->filled('issued_on') && $value < $request->input('issued_on')) {
                    $fail('The expiry date must be on or after the issue date.');
                }
            }],
            'notes' => 'nullable|string|max:3000',
            'document_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $newPath = null;
        $oldPath = $document->file_path;
        if ($request->hasFile('document_file')) {
            $file = $request->file('document_file');
            $newPath = $file->store('school-compliance', 'local');
            abort_if(! $newPath, 500, 'The document could not be stored. Please retry.');
            $data['file_path'] = $newPath;
            $data['original_filename'] = mb_substr($file->getClientOriginalName(), 0, 255);
        }
        unset($data['document_file']);
        try {
            $document->update($data);
        } catch (\Throwable $exception) {
            if ($newPath) {
                Storage::disk('local')->delete($newPath);
            }
            throw $exception;
        }
        if ($newPath && $oldPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return back()->with('success', 'School compliance document updated.');
    }

    public function download(SchoolComplianceDocument $document): StreamedResponse
    {
        abort_unless($document->file_path && Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $document->original_filename ?: basename($document->file_path));
    }

    public function destroy(SchoolComplianceDocument $document): RedirectResponse
    {
        $filePath = $document->file_path;
        $document->delete();
        if ($filePath) {
            Storage::disk('local')->delete($filePath);
        }

        return back()->with('success', 'School compliance document deleted.');
    }
}
