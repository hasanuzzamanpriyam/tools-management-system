<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function __construct(private readonly TokenService $tokens) {}

    public function validateRequest(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'device_fingerprint' => ['required', 'string'],
        ]);

        return response()->json($this->tokens->validate($data['token'], $data['device_fingerprint']));
    }

    public function activate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'device_fingerprint' => ['required', 'string'],
        ]);

        return response()->json($this->tokens->activate($data['token'], $data['device_fingerprint']));
    }
}
