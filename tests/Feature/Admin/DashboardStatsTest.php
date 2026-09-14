<?php

namespace Tests\Feature\Admin;

use App\Models\Purchase;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsRole(string $role): User
    {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user, 'sanctum');

        return $user;
    }

    public function test_guest_cannot_access_dashboard_stats(): void
    {
        $this->getJson('/api/admin/dashboard/stats')->assertStatus(401);
    }

    public function test_regular_user_cannot_access_dashboard_stats(): void
    {
        $this->actingAsRole(User::ROLE_USER);

        $this->getJson('/api/admin/dashboard/stats')->assertStatus(403);
    }

    public function test_admin_can_get_dashboard_stats(): void
    {
        $admin = $this->actingAsRole(User::ROLE_ADMIN);
        User::factory()->count(2)->create();
        $tools = Tool::factory()->count(2)->create();

        $this->getJson('/api/admin/dashboard/stats')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => [
                'total_revenue',
                'active_subscriptions',
                'total_tools',
                'total_users',
                'recent_purchases',
            ]])
            ->assertJsonPath('data.total_tools', 2)
            ->assertJsonPath('data.total_users', 3)
            ->assertJsonCount(0, 'data.recent_purchases');

        $this->assertNotNull($admin);
        $this->assertNotEmpty($tools);
    }

    public function test_total_revenue_is_sum_of_non_refunded_purchases(): void
    {
        $this->actingAsRole(User::ROLE_ADMIN);
        $tool = Tool::factory()->create();
        $buyer = User::factory()->create();

        Purchase::factory()->create(['user_id' => $buyer->id, 'tool_id' => $tool->id, 'amount' => 19.99]);
        Purchase::factory()->create(['user_id' => $buyer->id, 'tool_id' => $tool->id, 'amount' => 9.99, 'status' => Purchase::STATUS_REFUNDED]);

        $this->getJson('/api/admin/dashboard/stats')
            ->assertJsonPath('data.total_revenue', 19.99);
    }

    public function test_active_subscription_count_only_counts_active_subscriptions(): void
    {
        $this->actingAsRole(User::ROLE_ADMIN);
        $buyer = User::factory()->create();
        $subscription = Tool::factory()->create(['pricing_model' => Tool::PRICING_SUBSCRIPTION]);
        $oneTime = Tool::factory()->create(['pricing_model' => Tool::PRICING_ONE_TIME]);

        Purchase::factory()->create(['user_id' => $buyer->id, 'tool_id' => $subscription->id]);
        Purchase::factory()->create(['user_id' => $buyer->id, 'tool_id' => $subscription->id, 'status' => Purchase::STATUS_EXPIRED]);
        Purchase::factory()->create(['user_id' => $buyer->id, 'tool_id' => $oneTime->id]);

        $this->getJson('/api/admin/dashboard/stats')
            ->assertJsonPath('data.active_subscriptions', 1);
    }

    public function test_recent_purchases_are_newest_first_with_user_and_tool(): void
    {
        $this->actingAsRole(User::ROLE_ADMIN);
        $buyer = User::factory()->create(['name' => 'Ada Lovelace']);
        $tool = Tool::factory()->create(['name' => 'TimeSync']);

        $old = Purchase::factory()->create([
            'user_id' => $buyer->id,
            'tool_id' => $tool->id,
            'amount' => 5,
            'created_at' => now()->subDays(2),
        ]);
        $recent = Purchase::factory()->create([
            'user_id' => $buyer->id,
            'tool_id' => $tool->id,
            'amount' => 25,
            'created_at' => now(),
        ]);

        $this->getJson('/api/admin/dashboard/stats')
            ->assertJsonPath('data.recent_purchases.0.id', $recent->id)
            ->assertJsonPath('data.recent_purchases.0.user_name', 'Ada Lovelace')
            ->assertJsonPath('data.recent_purchases.0.tool_name', 'TimeSync')
            ->assertJsonPath('data.recent_purchases.0.amount', '25.00')
            ->assertJsonPath('data.recent_purchases.0.status', Purchase::STATUS_ACTIVE)
            ->assertJsonPath('data.recent_purchases.1.id', $old->id);
    }
}
