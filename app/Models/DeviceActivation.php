<?php

namespace App\Models;

use Database\Factories\DeviceActivationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['license_token_id', 'device_fingerprint', 'activated_at'])]
class DeviceActivation extends Model
{
    /** @use HasFactory<DeviceActivationFactory> */
    use HasFactory;

    public function licenseToken(): BelongsTo
    {
        return $this->belongsTo(LicenseToken::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activated_at' => 'datetime',
        ];
    }
}
