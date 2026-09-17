<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ToolResource;
use App\Models\Tool;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StoreController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ToolResource::collection(
            Tool::query()
                ->where('is_active', true)
                ->with('files')
                ->latest()
                ->get(),
        );
    }
}
