<?php

namespace Tests\Feature;

use App\Models\Tool;
use App\Models\User;
use App\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\StripeClient;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_checkout(): void
    {
        $tool = Tool::factory()->create();

        $this->postJson("/api/tools/{$tool->id}/checkout")->assertStatus(401);
    }

    public function test_authenticated_user_can_checkout(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $tool = Tool::factory()->create(['slug' => 'timesync']);

        $this->actingAs($user, 'sanctum');
        $this->mock(PurchaseService::class, function ($mock) {
            $mock->shouldReceive('checkout')
                ->once()
                ->andReturn(['id' => 'cs_test_123', 'url' => 'https://checkout.stripe.com/c/pay/cs_test_123']);
        });

        $this->postJson("/api/tools/{$tool->id}/checkout")
            ->assertStatus(200)
            ->assertJsonPath('data.session_id', 'cs_test_123')
            ->assertJsonPath('data.url', 'https://checkout.stripe.com/c/pay/cs_test_123');
    }

    public function test_inactive_tool_cannot_be_checked_out(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $tool = Tool::factory()->create(['is_active' => false]);

        $this->actingAs($user, 'sanctum');

        $this->postJson("/api/tools/{$tool->id}/checkout")->assertStatus(409);
    }

    public function test_missing_tool_returns_not_found(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($user, 'sanctum');

        $this->postJson('/api/tools/999/checkout')->assertStatus(404);
    }

    public function test_one_time_tool_builds_payment_session_payload(): void
    {
        $tool = Tool::factory()->create([
            'name' => 'TimeSync',
            'pricing_model' => Tool::PRICING_ONE_TIME,
            'price' => 19.99,
        ]);
        $user = User::factory()->create();

        $service = new PurchaseService($this->createMock(StripeClient::class));
        $payload = $service->buildCheckoutPayload($tool, $user);

        $this->assertSame('payment', $payload['mode']);
        $this->assertSame(1999, $payload['line_items'][0]['price_data']['unit_amount']);
        $this->assertArrayNotHasKey('recurring', $payload['line_items'][0]['price_data']);
        $this->assertSame((string) $user->id, $payload['client_reference_id']);
        $this->assertSame((string) $tool->id, $payload['metadata']['tool_id']);
        $this->assertSame($user->email, $payload['customer_email']);
    }

    public function test_subscription_tool_builds_subscription_session_payload(): void
    {
        $tool = Tool::factory()->create([
            'pricing_model' => Tool::PRICING_SUBSCRIPTION,
            'price' => 9.99,
        ]);
        $user = User::factory()->create();

        $service = new PurchaseService($this->createMock(StripeClient::class));
        $payload = $service->buildCheckoutPayload($tool, $user);

        $this->assertSame('subscription', $payload['mode']);
        $this->assertSame(999, $payload['line_items'][0]['price_data']['unit_amount']);
        $this->assertSame(['interval' => 'month'], $payload['line_items'][0]['price_data']['recurring']);
    }
}
