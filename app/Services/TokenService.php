<?php

namespace App\Services;

use App\Models\LicenseToken;
use App\Models\Purchase;
use Illuminate\Support\Str;

class TokenService
{
    /**
     * Generate a license token for a purchase and store its hash.
     *
     * The plaintext token is returned exactly once so it can be handed to the
     * customer; only the SHA-256 hash is persisted.
     */
    public function generate(Purchase $purchase): string
    {
        $token = $this->issue();

        LicenseToken::create([
            'purchase_id' => $purchase->id,
            'token_hash' => $this->hash($token),
            'device_limit' => $purchase->tool->device_limit,
            'is_active' => true,
            'expires_at' => $purchase->expires_at,
        ]);

        return $token;
    }

    public function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    public function issue(): string
    {
        return Str::random(64);
    }
}
