<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ToolFileUploadRequest;
use App\Models\Tool;
use App\Models\ToolFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ToolFileController extends Controller
{
    public function store(ToolFileUploadRequest $request, Tool $tool): JsonResponse
    {
        $uploadedFile = $request->file('file');

        $toolFile = $tool->files()->create([
            'file_path' => $uploadedFile->storeAs("tools/{$tool->id}", $uploadedFile->getClientOriginalName()),
            'file_type' => strtolower($uploadedFile->getClientOriginalExtension()),
            'version' => $request->string('version')->toString(),
            'changelog' => $request->input('changelog'),
        ]);

        return response()->json($toolFile, 201);
    }

    public function destroy(Tool $tool, ToolFile $file): Response
    {
        Storage::delete($file->file_path);

        $file->delete();

        return response()->noContent();
    }
}
