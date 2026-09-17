<?php

namespace Tests\Feature;

use App\Models\LicenseToken;
use App\Models\Purchase;
use App\Models\Tool;
use App\Models\ToolFile;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use ZipArchive;

class PerfSecurityTest extends TestCase
{
    use RefreshDatabase;

    const WEBHOOK_SECRET = 'whsec_perf_1234';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.stripe.webhook_secret' => self::WEBHOOK_SECRET]);
    }

    private function makeZipFile(Tool $tool): ToolFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'src');
        $zip = new ZipArchive;
        $zip->open($tmp, ZipArchive::OVERWRITE);
        $zip->addFromString('readme.txt', 'hello world');
        $zip->close();

        Storage::disk('local')->putFileAs("tools/{$tool->id}", $tmp, 'bundle.zip');

        return ToolFile::query()->create([
            'tool_id' => $tool->id,
            'file_path' => "tools/{$tool->id}/bundle.zip",
            'file_type' => 'zip',
            'version' => '1.0.0',
            'changelog' => null,
        ]);
    }

    private function buy(Tool $tool, User $user): string
    {
        $purchase = Purchase::factory()->create([
            'user_id' => $user->id,
            'tool_id' => $tool->id,
            'status' => Purchase::STATUS_ACTIVE,
        ]);

        return (new TokenService)->generate($purchase);
    }

    private function tokenModel(string $token): LicenseToken
    {
        return LicenseToken::where('token_hash', (new TokenService)->hash($token))->first();
    }

    private function postWebhook(array $event): TestResponse
    {
        $payload = json_encode($event);
        $time = time();
        $signature = hash_hmac('sha256', $time.'.'.$payload, self::WEBHOOK_SECRET);
        $header = ['HTTP_STRIPE_SIGNATURE' => "t={$time},v1={$signature}", 'CONTENT_TYPE' => 'application/json'];

        return $this->call('POST', '/api/stripe/webhook', [], [], [], $header, $payload);
    }

    public function test_license_validation_result_is_cached(): void
    {
        $purchaser = User::factory()->create();
        $tool = Tool::factory()->create(['device_limit' => 2]);
        $token = $this->buy($tool, $purchaser);

        $path = '/api/license/validate';

        DB::enableQueryLog();
        $this->postJson($path, ['token' => $token, 'device_fingerprint' => 'device-a'])
            ->assertOk()
            ->assertJson(['valid' => true]);
        $first = count(DB::getQueryLog());
        DB::flushQueryLog();

        $this->postJson($path, ['token' => $token, 'device_fingerprint' => 'device-a'])
            ->assertOk()
            ->assertJson(['valid' => true]);
        $second = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan($first, $second);
    }

    public function test_activation_invalidates_cached_validation(): void
    {
        $purchaser = User::factory()->create();
        $tool = Tool::factory()->create(['device_limit' => 1]);
        $token = $this->buy($tool, $purchaser);

        (new TokenService)->activate($token, 'device-a');

        // Slot is full -> cached for this device.
        $this->postJson('/api/license/validate', ['token' => $token, 'device_fingerprint' => 'device-b'])
            ->assertOk()
            ->assertJsonPath('reason', 'device_limit_exceeded');

        // Device a releases its slot.
        $this->tokenModel($token)->activations()->where('device_fingerprint', 'device-a')->delete();

        // Activating a new device invalidates the cached validation.
        $this->postJson('/api/license/activate', ['token' => $token, 'device_fingerprint' => 'device-b'])
            ->assertOk()
            ->assertJson(['valid' => true]);

        $this->postJson('/api/license/validate', ['token' => $token, 'device_fingerprint' => 'device-b'])
            ->assertOk()
            ->assertJson(['valid' => true]);
    }

    public function test_payment_failure_webhook_invalidates_cached_validation(): void
    {
        $purchaser = User::factory()->create();
        $tool = Tool::factory()->create(['pricing_model' => Tool::PRICING_SUBSCRIPTION, 'device_limit' => 2]);
        $purchase = Purchase::factory()->create([
            'user_id' => $purchaser->id,
            'tool_id' => $tool->id,
            'stripe_subscription_id' => 'sub_perf_1',
            'status' => Purchase::STATUS_ACTIVE,
        ]);
        $token = (new TokenService)->generate($purchase);

        $this->postJson('/api/license/validate', ['token' => $token, 'device_fingerprint' => 'device-a'])
            ->assertOk()
            ->assertJson(['valid' => true]);

        $this->postWebhook([
            'id' => 'evt_perf_1',
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'subscription' => 'sub_perf_1',
                    'lines' => [
                        'data' => [
                            ['period' => ['end' => 9999999999]],
                        ],
                    ],
                ],
            ],
        ])->assertOk();

        $this->postJson('/api/license/validate', ['token' => $token, 'device_fingerprint' => 'device-a'])
            ->assertOk()
            ->assertJsonPath('valid', false)
            ->assertJsonPath('reason', 'unpaid');
    }

    public function test_license_validate_is_rate_limited(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $this->postJson('/api/license/validate', ['token' => 'missing', 'device_fingerprint' => 'device-a'])
                ->assertOk();
        }

        $this->postJson('/api/license/validate', ['token' => 'missing', 'device_fingerprint' => 'device-a'])
            ->assertStatus(429);
    }

    public function test_download_is_rate_limited(): void
    {
        $purchaser = User::factory()->create();
        $tool = Tool::factory()->create();
        $this->makeZipFile($tool);
        $this->buy($tool, $purchaser);

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($purchaser)->get("/api/tools/{$tool->id}/download")->assertOk();
        }

        $this->actingAs($purchaser)->get("/api/tools/{$tool->id}/download")->assertStatus(429);
    }

    public function test_admin_tool_actions_are_audited(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $this->actingAs($admin, 'sanctum');

        $created = $this->postJson('/api/admin/tools', [
            'name' => 'AuditTool',
            'slug' => 'audittool',
            'description' => 'Audited.',
            'type' => Tool::TYPE_DESKTOP,
            'pricing_model' => Tool::PRICING_ONE_TIME,
            'price' => 9.99,
            'device_limit' => 1,
        ])->assertStatus(201)->json('data');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'tool.created',
            'entity_type' => 'tools',
            'entity_id' => $created['id'],
        ]);

        $this->patchJson("/api/admin/tools/{$created['id']}", [
            'name' => 'AuditTool',
            'slug' => 'audittool',
            'description' => 'Audited.',
            'type' => Tool::TYPE_DESKTOP,
            'pricing_model' => Tool::PRICING_ONE_TIME,
            'price' => 12.99,
            'device_limit' => 1,
        ])->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'tool.updated',
            'entity_type' => 'tools',
            'entity_id' => $created['id'],
        ]);

        $this->deleteJson("/api/admin/tools/{$created['id']}")->assertNoContent();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'tool.deleted',
            'entity_type' => 'tools',
            'entity_id' => $created['id'],
        ]);
    }

    public function test_admin_user_changes_are_audited(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $target = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/users/{$target->id}", ['role' => User::ROLE_ADMIN])
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'user.updated',
            'entity_type' => 'users',
            'entity_id' => $target->id,
        ]);
    }
}
