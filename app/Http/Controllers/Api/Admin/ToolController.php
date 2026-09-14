<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ToolStoreRequest;
use App\Http\Requests\ToolUpdateRequest;
use App\Http\Resources\ToolResource;
use App\Models\Tool;
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

        return (new ToolResource($tool))->response()->setStatusCode(201);
    }

    public function show(Tool $tool): ToolResource
    {
        return new ToolResource($tool->load('files'));
    }

    public function update(ToolUpdateRequest $request, Tool $tool): ToolResource
    {
        $tool->update($request->validated());

        return new ToolResource($tool->load('files'));
    }

    public function destroy(Tool $tool): Response
    {
        $tool->delete();

        return response()->noContent();
    }
}
