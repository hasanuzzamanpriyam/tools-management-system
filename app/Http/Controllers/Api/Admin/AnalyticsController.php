<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analytics) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'revenue' => $this->analytics->revenue(),
                'usage' => $this->analytics->usage(),
                'churn' => $this->analytics->churn(),
            ],
        ]);
    }
}
