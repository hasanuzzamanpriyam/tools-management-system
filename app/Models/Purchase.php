<?php

namespace App\Models;

use Database\Factories\PurchaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['user_id', 'tool_id', 'stripe_payment_id', 'stripe_subscription_id', 'amount', 'status', 'expires_at'])]
class Purchase extends Model
{
    /** @use HasFactory<PurchaseFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_PAYMENT_FAILED = 'payment_failed';

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }

    public function licenseToken(): HasOne
    {
        return $this->hasOne(LicenseToken::class);
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
            'expires_at' => 'datetime',
        ];
    }
}
