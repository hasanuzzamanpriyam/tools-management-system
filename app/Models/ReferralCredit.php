<?php

namespace App\Models;

use Database\Factories\ReferralCreditFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['referrer_id', 'purchase_id', 'amount', 'redeemed_amount', 'redeemed_at'])]
class ReferralCredit extends Model
{
    /** @use HasFactory<ReferralCreditFactory> */
    use HasFactory;

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'redeemed_amount' => 'decimal:2',
            'redeemed_at' => 'datetime',
        ];
    }
}
