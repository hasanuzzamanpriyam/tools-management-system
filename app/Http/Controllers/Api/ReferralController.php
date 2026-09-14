<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CreditsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function __construct(private readonly CreditsService $credits) {}

    public function credits(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'balance' => $this->credits->balance($user),
            'history' => $this->credits->history($user)->map(function ($credit) {
                return [
                    'id' => $credit->id,
                    'amount' => (float) $credit->amount,
                    'remaining' => round($credit->amount - $credit->redeemed_amount, 2),
                    'redeemed_at' => $credit->redeemed_at?->toISOString(),
                    'created_at' => $credit->created_at?->toISOString(),
                    'tool' => $credit->purchase?->tool?->name,
                ];
            }),
        ]);
    }
}
