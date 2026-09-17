<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ToolStoreRequest;
use App\Http\Requests\ToolUpdateRequest;
use App\Http\Resources\ToolResource;
use App\Models\Tool;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class ToolController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ToolResource::collection(
            Tool::query()->with('files')->latest()->paginate()
        );
    }

    public function store(ToolStoreRequest $request): JsonResponse
    {
        $tool = Tool::create($request->validated());

        (new AuditLogger)->record($request, 'tool.created', $tool);

        return (new ToolResource($tool))->response()->setStatusCode(201);
    }

    public function show(Tool $tool): ToolResource
    {
        return new ToolResource($tool->load('files'));
    }

    public function update(ToolUpdateRequest $request, Tool $tool): ToolResource
    {
        $before = $tool->getOriginal();

        $tool->update($request->validated());

        $changes = $tool->getChanges();
        (new AuditLogger)->record($request, 'tool.updated', $tool, [
            'before' => collect($before)->only(array_keys($changes))->all(),
            'after' => collect($changes)->except(['updated_at'])->all(),
        ]);

        return new ToolResource($tool->load('files'));
    }

    public function destroy(Tool $tool): Response
    {
        (new AuditLogger)->record(request(), 'tool.deleted', $tool, [
            'name' => $tool->name,
            'slug' => $tool->slug,
        ]);

        $tool->delete();

        return response()->noContent();
    }
}
