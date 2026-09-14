<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tool;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StripeController extends Controller
{
    public function __construct(private readonly PurchaseService $purchases) {}

    public function checkout(Request $request, Tool $tool): JsonResponse
    {
        if (! $tool->is_active) {
            return response()->json(['message' => 'This tool is not available for purchase.'], 409);
        }

        $session = $this->purchases->checkout($tool, $request->user());

        return response()->json([
            'data' => [
                'session_id' => $session['id'],
                'url' => $session['url'],
            ],
        ]);
    }
}
