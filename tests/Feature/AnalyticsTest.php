<?php

namespace Tests\Feature;

use App\Models\DeviceActivation;
use App\Models\LicenseToken;
use App\Models\Purchase;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(Purchase $purchase): LicenseToken
    {
        return LicenseToken::factory()->create([
            'purchase_id' => $purchase->id,
            'is_active' => true,
            'device_limit' => $purchase->tool->device_limit,
        ]);
    }

    public function test_admin_can_fetch_monthly_revenue_series(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $tool = Tool::factory()->create(['price' => 19.99]);

        Purchase::factory()->create([
            'tool_id' => $tool->id,
            'amount' => 19.99,
            'status' => Purchase::STATUS_ACTIVE,
            'created_at' => now(),
        ]);
        Purchase::factory()->create([
            'tool_id' => $tool->id,
            'amount' => 9.99,
            'status' => Purchase::STATUS_ACTIVE,
            'created_at' => now()->subMonths(2),
        ]);
        Purchase::factory()->create([
            'tool_id' => $tool->id,
            'amount' => 99.00,
            'status' => Purchase::STATUS_REFUNDED,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/analytics')
            ->assertOk();

        $revenue = $response->json('data.revenue');
        $this->assertCount(12, $revenue);

        $current = collect($revenue)->firstWhere('month', now()->format('Y-m'));
        $this->assertEquals(19.99, $current['revenue']);
        $this->assertSame(1, $current['purchases']);

        $twoMonthsAgo = collect($revenue)->firstWhere('month', now()->subMonths(2)->format('Y-m'));
        $this->assertEquals(9.99, $twoMonthsAgo['revenue']);
    }

    public function test_revenue_series_is_ascending_last_twelve_months(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $months = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/analytics')
            ->assertOk()
            ->json('data.revenue');

        $this->assertSame(12, count($months));
        $this->assertSame(now()->startOfMonth()->subMonths(11)->format('Y-m'), $months[0]['month']);
        $this->assertSame(now()->format('Y-m'), $months[11]['month']);
        $this->assertTrue(collect($months)->every(fn ($month) => is_float($month['revenue']) || $month['revenue'] === 0));
    }

    public function test_usage_reports_active_devices_per_tool(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $tool = Tool::factory()->create(['device_limit' => 1]);
        $purchaser = User::factory()->create();

        $purchase = Purchase::factory()->create([
            'user_id' => $purchaser->id,
            'tool_id' => $tool->id,
            'status' => Purchase::STATUS_ACTIVE,
        ]);
        $token = $this->tokenFor($purchase);

        DeviceActivation::factory()->create(['license_token_id' => $token->id, 'device_fingerprint' => 'device-a']);
        DeviceActivation::factory()->create(['license_token_id' => $token->id, 'device_fingerprint' => 'device-b']);

        $refunded = Purchase::factory()->create([
            'user_id' => $purchaser->id,
            'tool_id' => $tool->id,
            'status' => Purchase::STATUS_REFUNDED,
        ]);
        DeviceActivation::factory()->create([
            'license_token_id' => $this->tokenFor($refunded)->id,
            'device_fingerprint' => 'device-c',
        ]);

        Tool::factory()->create(['name' => 'Empty', 'slug' => 'empty', 'device_limit' => 3]);

        $usage = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/analytics')
            ->assertOk()
            ->json('data.usage');

        $entry = collect($usage)->firstWhere('slug', $tool->slug);
        $this->assertSame(3, $entry['activations_total']);
        $this->assertSame(2, $entry['active_devices']);
        $this->assertSame(1, $entry['device_limit']);

        $empty = collect($usage)->firstWhere('slug', 'empty');
        $this->assertSame(0, $empty['activations_total']);
        $this->assertSame(0, $empty['active_devices']);
    }

    public function test_churn_counts_expired_subscriptions_per_month(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $tool = Tool::factory()->create(['pricing_model' => Tool::PRICING_SUBSCRIPTION]);

        Purchase::factory()->create([
            'tool_id' => $tool->id,
            'status' => Purchase::STATUS_EXPIRED,
            'created_at' => now()->subMonths(3)->addDays(1),
        ]);
        Purchase::factory()->create([
            'tool_id' => $tool->id,
            'status' => Purchase::STATUS_PAYMENT_FAILED,
            'created_at' => now()->subMonths(3)->addDays(2),
        ]);
        Purchase::factory()->create([
            'tool_id' => $tool->id,
            'status' => Purchase::STATUS_EXPIRED,
            'created_at' => now(),
        ]);

        $churn = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/analytics')
            ->assertOk()
            ->json('data.churn');

        $this->assertCount(12, $churn);

        $target = collect($churn)->firstWhere('month', now()->subMonths(3)->format('Y-m'));
        $this->assertSame(2, $target['churned']);

        $current = collect($churn)->firstWhere('month', now()->format('Y-m'));
        $this->assertSame(1, $current['churned']);
    }

    public function test_analytics_requires_admin(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/admin/analytics')
            ->assertStatus(403);
    }
}
