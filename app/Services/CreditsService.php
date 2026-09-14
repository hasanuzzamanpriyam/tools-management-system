<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\ReferralCredit;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CreditsService
{
    /**
     * Available (unredeemed) credit balance in dollars.
     */
    public function balance(User $user): float
    {
        return (float) ReferralCredit::query()
            ->where('referrer_id', $user->id)
            ->selectRaw('COALESCE(SUM(amount - redeemed_amount), 0) as total')
            ->value('total');
    }

    /**
     * Recent credit history for the user.
     *
     * @return Collection<int, ReferralCredit>
     */
    public function history(User $user): Collection
    {
        return ReferralCredit::query()
            ->with('purchase.tool')
            ->where('referrer_id', $user->id)
            ->latest('id')
            ->limit(50)
            ->get();
    }

    /**
     * Award the referrer the tool's referral credit on the referred
     * user's very first purchase.
     */
    public function creditFirstPurchase(Purchase $purchase): ?ReferralCredit
    {
        $buyer = $purchase->user()->first();
        $referrer = $buyer?->referredBy()->first();

        if (! $buyer || ! $referrer) {
            return null;
        }

        if ($buyer->purchases()->count() !== 1) {
            return null;
        }

        if (ReferralCredit::where('referrer_id', $referrer->id)
            ->where('purchase_id', $purchase->id)
            ->exists()) {
            return null;
        }

        $amount = (float) $purchase->tool->referral_credits;

        if ($amount <= 0) {
            return null;
        }

        return ReferralCredit::create([
            'referrer_id' => $referrer->id,
            'purchase_id' => $purchase->id,
            'amount' => $amount,
        ]);
    }

    /**
     * Consume credits (oldest first) up to the given amount.
     */
    public function redeem(User $user, float $amount): void
    {
        $remaining = round($amount, 2);

        $credits = ReferralCredit::query()
            ->where('referrer_id', $user->id)
            ->whereColumn('redeemed_amount', '<', 'amount')
            ->orderBy('id')
            ->get();

        foreach ($credits as $credit) {
            if ($remaining <= 0) {
                break;
            }

            $available = round($credit->amount - $credit->redeemed_amount, 2);
            $take = min($available, $remaining);

            $credit->redeemed_amount = round($credit->redeemed_amount + $take, 2);
            $credit->redeemed_at ??= $credit->redeemed_amount >= $credit->amount ? now() : null;
            $credit->save();

            $remaining = round($remaining - $take, 2);
        }
    }
}
