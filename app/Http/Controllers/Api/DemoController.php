<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tool;
use Illuminate\Http\JsonResponse;

class DemoController extends Controller
{
    public function show(Tool $tool): JsonResponse
    {
        if (! $tool->has_demo || ! $tool->demo_url) {
            return response()->json(['message' => 'No demo available for this tool.'], 404);
        }

        return response()->json(['demo_url' => $tool->demo_url]);
    }
}
