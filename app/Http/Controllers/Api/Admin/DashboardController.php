<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $totalRevenue = Purchase::query()
            ->where('status', '!=', Purchase::STATUS_REFUNDED)
            ->sum('amount');

        $activeSubscriptions = Purchase::query()
            ->where('status', Purchase::STATUS_ACTIVE)
            ->whereHas('tool', fn ($query) => $query->where('pricing_model', Tool::PRICING_SUBSCRIPTION))
            ->count();

        $recentPurchases = Purchase::query()
            ->with(['user:id,name,email', 'tool:id,name,slug'])
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Purchase $purchase) => [
                'id' => $purchase->id,
                'user_name' => $purchase->user?->name,
                'user_email' => $purchase->user?->email,
                'tool_name' => $purchase->tool?->name,
                'tool_slug' => $purchase->tool?->slug,
                'amount' => $purchase->amount,
                'status' => $purchase->status,
                'created_at' => $purchase->created_at,
            ]);

        return response()->json([
            'data' => [
                'total_revenue' => (float) $totalRevenue,
                'active_subscriptions' => $activeSubscriptions,
                'total_tools' => Tool::count(),
                'total_users' => User::count(),
                'recent_purchases' => $recentPurchases,
            ],
        ]);
    }
}
