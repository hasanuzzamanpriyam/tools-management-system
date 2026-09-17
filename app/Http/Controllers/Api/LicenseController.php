<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TokenService;
use App\Support\LicenseCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LicenseController extends Controller
{
    public function __construct(private readonly TokenService $tokens) {}

    public function validateRequest(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'device_fingerprint' => ['required', 'string'],
        ]);

        $cache = new LicenseCache;

        $result = Cache::remember(
            $cache->key($data['token'], $data['device_fingerprint']),
            now()->addSeconds((int) config('services.license_cache_ttl', 60)),
            fn () => $this->tokens->validate($data['token'], $data['device_fingerprint']),
        );

        return response()->json($result);
    }

    public function activate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'device_fingerprint' => ['required', 'string'],
        ]);

        $result = $this->tokens->activate($data['token'], $data['device_fingerprint']);

        (new LicenseCache)->bustToken($data['token']);

        return response()->json($result);
    }
}
