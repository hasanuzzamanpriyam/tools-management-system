<?php

namespace Tests\Feature;

use App\Models\LicenseToken;
use App\Models\Purchase;
use App\Models\Tool;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a usable license for a fresh purchase and return the plaintext token.
     */
    private function makeToken(array $tool = [], array $purchase = []): string
    {
        $user = User::factory()->create();
        $toolModel = Tool::factory()->create(array_merge(['device_limit' => 1], $tool));
        $purchase = Purchase::factory()->create(array_merge([
            'user_id' => $user->id,
            'tool_id' => $toolModel->id,
            'status' => Purchase::STATUS_ACTIVE,
        ], $purchase));

        return (new TokenService)->generate($purchase);
    }

    private function licenseFor(string $token): LicenseToken
    {
        return LicenseToken::where('token_hash', (new TokenService)->hash($token))->firstOrFail();
    }

    public function test_validate_requires_token_and_device_fingerprint(): void
    {
        $this->postJson('/api/license/validate', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['token', 'device_fingerprint']);
    }

    public function test_unknown_token_returns_not_found(): void
    {
        $this->postJson('/api/license/validate', [
            'token' => 'does-not-exist',
            'device_fingerprint' => 'fp_1',
        ])->assertStatus(200)
            ->assertJsonPath('valid', false)
            ->assertJsonPath('reason', 'not_found');
    }

    public function test_valid_token_with_new_device_activates(): void
    {
        $token = $this->makeToken(['device_limit' => 2]);
        $license = $this->licenseFor($token);

        $this->postJson('/api/license/activate', [
            'token' => $token,
            'device_fingerprint' => 'fp_1',
        ])->assertStatus(200)
            ->assertJsonPath('valid', true)
            ->assertJsonPath('purchase_id', $license->purchase_id)
            ->assertJsonPath('tool_id', $license->purchase->tool_id);

        $this->assertDatabaseHas('device_activations', [
            'license_token_id' => $license->id,
            'device_fingerprint' => 'fp_1',
        ]);
    }

    public function test_duplicate_activation_is_idempotent(): void
    {
        $token = $this->makeToken(['device_limit' => 2]);
        $license = $this->licenseFor($token);

        $this->postJson('/api/license/activate', [
            'token' => $token,
            'device_fingerprint' => 'fp_1',
        ])->assertJsonPath('valid', true);

        $this->postJson('/api/license/activate', [
            'token' => $token,
            'device_fingerprint' => 'fp_1',
        ])->assertStatus(200)
            ->assertJsonPath('valid', true);

        $this->assertDatabaseCount('device_activations', 1);
    }

    public function test_device_limit_exceeded_for_new_device(): void
    {
        $token = $this->makeToken(['device_limit' => 1]);
        $license = $this->licenseFor($token);

        $license->activations()->create(['device_fingerprint' => 'fp_1', 'activated_at' => now()]);

        $this->postJson('/api/license/activate', [
            'token' => $token,
            'device_fingerprint' => 'fp_2',
        ])->assertStatus(200)
            ->assertJsonPath('valid', false)
            ->assertJsonPath('reason', 'device_limit_exceeded');
    }

    public function test_existing_device_can_always_validate_at_limit(): void
    {
        $token = $this->makeToken(['device_limit' => 1]);
        $license = $this->licenseFor($token);
        $license->activations()->create(['device_fingerprint' => 'fp_1', 'activated_at' => now()]);

        $this->postJson('/api/license/validate', [
            'token' => $token,
            'device_fingerprint' => 'fp_1',
        ])->assertStatus(200)
            ->assertJsonPath('valid', true);
    }

    public function test_validate_reports_device_limit_exceeded_for_new_device(): void
    {
        $token = $this->makeToken(['device_limit' => 1]);
        $license = $this->licenseFor($token);
        $license->activations()->create(['device_fingerprint' => 'fp_1', 'activated_at' => now()]);

        $this->postJson('/api/license/validate', [
            'token' => $token,
            'device_fingerprint' => 'fp_2',
        ])->assertStatus(200)
            ->assertJsonPath('valid', false)
            ->assertJsonPath('reason', 'device_limit_exceeded');
    }

    public function test_inactive_token_returns_inactive(): void
    {
        $token = $this->makeToken();
        $this->licenseFor($token)->update(['is_active' => false]);

        $this->postJson('/api/license/validate', [
            'token' => $token,
            'device_fingerprint' => 'fp_1',
        ])->assertJsonPath('valid', false)
            ->assertJsonPath('reason', 'inactive');
    }

    public function test_expired_token_returns_expired(): void
    {
        $purchase = now(); // placeholder to satisfy linter
        $token = $this->makeToken([], [
            'expires_at' => now()->subDay(),
        ]);
        $this->assertNotNull($purchase);
        $this->licenseFor($token)->update(['expires_at' => now()->subDay()]);

        $this->postJson('/api/license/validate', [
            'token' => $token,
            'device_fingerprint' => 'fp_1',
        ])->assertJsonPath('valid', false)
            ->assertJsonPath('reason', 'expired');
    }

    public function test_unpaid_purchase_returns_unpaid(): void
    {
        $token = $this->makeToken([], ['status' => Purchase::STATUS_EXPIRED]);

        $this->postJson('/api/license/validate', [
            'token' => $token,
            'device_fingerprint' => 'fp_1',
        ])->assertJsonPath('valid', false)
            ->assertJsonPath('reason', 'unpaid');
    }

    public function test_valid_token_reports_purchase_and_tool_and_expires_at(): void
    {
        $token = $this->makeToken([], [
            'expires_at' => now()->addMonth(),
        ]);
        $license = $this->licenseFor($token);

        $this->postJson('/api/license/validate', [
            'token' => $token,
            'device_fingerprint' => 'fp_1',
        ])->assertStatus(200)
            ->assertJsonPath('valid', true)
            ->assertJsonPath('purchase_id', $license->purchase_id)
            ->assertJsonPath('tool_id', $license->purchase->tool_id)
            ->assertJsonStructure(['valid', 'purchase_id', 'tool_id', 'expires_at']);
    }
}
