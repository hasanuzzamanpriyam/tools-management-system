<?php

namespace App\Services;

use App\Models\LicenseToken;
use App\Models\Purchase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class TokenService
{
    /**
     * Generate a license token for a purchase and store its hash.
     *
     * The plaintext token is returned exactly once so it can be handed to the
     * customer; only the SHA-256 hash is persisted for lookup, while the
     * plaintext is kept encrypted for later configuration injection.
     */
    public function generate(Purchase $purchase): string
    {
        $token = $this->issue();

        LicenseToken::create([
            'purchase_id' => $purchase->id,
            'token_hash' => $this->hash($token),
            'token_value' => Crypt::encryptString($token),
            'device_limit' => $purchase->tool->device_limit,
            'is_active' => true,
            'expires_at' => $purchase->expires_at,
        ]);

        return $token;
    }

    /**
     * Validate that a token can be used by the given device.
     *
     * @return array<string, mixed>
     */
    public function validate(string $token, ?string $deviceFingerprint = null): array
    {
        $license = $this->resolve($token);

        if (! $license) {
            return $this->invalid('not_found');
        }

        if ($reason = $this->unusableReason($license)) {
            return $this->invalid($reason);
        }

        if ($deviceFingerprint && ! $this->deviceAllowed($license, $deviceFingerprint)) {
            return $this->invalid('device_limit_exceeded');
        }

        return $this->valid($license);
    }

    /**
     * Register a device fingerprint on first run, enforcing the device limit.
     *
     * @return array<string, mixed>
     */
    public function activate(string $token, string $deviceFingerprint): array
    {
        $license = $this->resolve($token);

        if (! $license) {
            return $this->invalid('not_found');
        }

        if ($reason = $this->unusableReason($license)) {
            return $this->invalid($reason);
        }

        if (! $this->deviceAllowed($license, $deviceFingerprint)) {
            return $this->invalid('device_limit_exceeded');
        }

        $license->activations()->firstOrCreate(
            ['device_fingerprint' => $deviceFingerprint],
            ['activated_at' => now()],
        );

        return $this->valid($license);
    }

    public function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    public function issue(): string
    {
        return Str::random(64);
    }

    private function resolve(string $token): ?LicenseToken
    {
        return LicenseToken::query()
            ->with('purchase')
            ->where('token_hash', $this->hash($token))
            ->first();
    }

    private function unusableReason(LicenseToken $license): ?string
    {
        if (! $license->is_active) {
            return 'inactive';
        }

        if ($license->expires_at && $license->expires_at->isPast()) {
            return 'expired';
        }

        if ($license->purchase->status !== Purchase::STATUS_ACTIVE) {
            return 'unpaid';
        }

        return null;
    }

    private function deviceAllowed(LicenseToken $license, string $fingerprint): bool
    {
        if ($license->activations()->where('device_fingerprint', $fingerprint)->exists()) {
            return true;
        }

        return $license->activations()->count() < $license->device_limit;
    }

    /**
     * @return array<string, mixed>
     */
    private function valid(LicenseToken $license): array
    {
        return [
            'valid' => true,
            'purchase_id' => $license->purchase_id,
            'tool_id' => $license->purchase->tool_id,
            'expires_at' => $license->expires_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function invalid(string $reason): array
    {
        return [
            'valid' => false,
            'reason' => $reason,
        ];
    }
}
