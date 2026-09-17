<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class LicenseCache
{
    private const RESULT_KEY = 'license:v:%d:%s:%s';

    private const BUST_KEY = 'license:bust:%s';

    /**
     * Build the cache key for a validation result. The version is bumped
     * whenever the license state changes so stale results are never served.
     */
    public function key(string $token, string $deviceFingerprint): string
    {
        $tokenHash = hash('sha256', $token);
        $version = (int) Cache::get($this->bustKey($tokenHash), 0);

        return sprintf(self::RESULT_KEY, $version, $tokenHash, hash('sha256', $deviceFingerprint));
    }

    /**
     * Invalidate every cached result belonging to a token.
     */
    public function bustToken(string $token): void
    {
        $this->bustTokenHash(hash('sha256', $token));
    }

    /**
     * Invalidate every cached result belonging to a stored token hash.
     */
    public function bustTokenHash(string $tokenHash): void
    {
        Cache::increment($this->bustKey($tokenHash));
    }

    private function bustKey(string $tokenHash): string
    {
        return sprintf(self::BUST_KEY, $tokenHash);
    }
}
