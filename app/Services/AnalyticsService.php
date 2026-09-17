<?php

namespace App\Services;

use App\Models\DeviceActivation;
use App\Models\Purchase;
use App\Models\Tool;
use Illuminate\Support\Collection;

class AnalyticsService
{
    private const WINDOW_MONTHS = 12;

    public function revenue(): array
    {
        $months = $this->months();

        $rows = Purchase::query()
            ->where('status', '!=', Purchase::STATUS_REFUNDED)
            ->where('created_at', '>=', $months->first()->startOfMonth())
            ->get(['amount', 'created_at']);

        $grouped = $rows->groupBy(fn (Purchase $purchase) => $purchase->created_at->format('Y-m'));

        return $months->map(function (\DateTimeInterface $month) use ($grouped) {
            $rows = $grouped->get($month->format('Y-m'), collect());

            return [
                'month' => $month->format('Y-m'),
                'label' => $month->format('M'),
                'revenue' => (float) $rows->sum('amount'),
                'purchases' => $rows->count(),
            ];
        })->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function usage(): array
    {
        return Tool::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function (Tool $tool) {
                $activations = DeviceActivation::query()
                    ->whereHas('licenseToken.purchase', fn ($query) => $query->where('tool_id', $tool->id));

                $activeDevices = (clone $activations)
                    ->whereHas('licenseToken', fn ($query) => $query->where('is_active', true))
                    ->whereHas(
                        'licenseToken.purchase',
                        fn ($query) => $query->where('tool_id', $tool->id)->where('status', Purchase::STATUS_ACTIVE),
                    );

                return [
                    'id' => $tool->id,
                    'name' => $tool->name,
                    'slug' => $tool->slug,
                    'type' => $tool->type,
                    'activations_total' => $activations->count(),
                    'active_devices' => $activeDevices->count(),
                    'device_limit' => $tool->device_limit,
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function churn(): array
    {
        $months = $this->months();

        $rows = Purchase::query()
            ->whereIn('status', [Purchase::STATUS_EXPIRED, Purchase::STATUS_PAYMENT_FAILED])
            ->whereHas('tool', fn ($query) => $query->where('pricing_model', Tool::PRICING_SUBSCRIPTION))
            ->where('created_at', '>=', $months->first()->startOfMonth())
            ->get(['created_at']);

        $grouped = $rows->groupBy(fn (Purchase $purchase) => $purchase->created_at->format('Y-m'));

        $activeSubscriptions = Purchase::query()
            ->where('status', Purchase::STATUS_ACTIVE)
            ->whereHas('tool', fn ($query) => $query->where('pricing_model', Tool::PRICING_SUBSCRIPTION))
            ->count();

        return $months->map(function (\DateTimeInterface $month) use ($grouped, $activeSubscriptions) {
            $monthKey = $month->format('Y-m');

            return [
                'month' => $monthKey,
                'label' => $month->format('M'),
                'churned' => ($grouped->get($monthKey) ?? collect())->count(),
                'active_subscriptions' => $activeSubscriptions,
            ];
        })->all();
    }

    /**
     * @return Collection<int, \DateTimeInterface>
     */
    private function months(): Collection
    {
        return collect(range(self::WINDOW_MONTHS - 1, 0))
            ->map(fn (int $offset) => now()->startOfMonth()->subMonths($offset));
    }
}
