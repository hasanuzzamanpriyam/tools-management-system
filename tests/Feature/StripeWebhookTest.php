<?php

namespace Tests\Feature;

use App\Models\LicenseToken;
use App\Models\Purchase;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $webhookSecret = 'whsec_test_1234';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.stripe.webhook_secret' => $this->webhookSecret]);
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function postWebhook(array $event): TestResponse
    {
        $payload = json_encode($event);
        $time = time();
        $signature = hash_hmac('sha256', $time.'.'.$payload, $this->webhookSecret);
        $header = ['HTTP_STRIPE_SIGNATURE' => "t={$time},v1={$signature}", 'CONTENT_TYPE' => 'application/json'];

        return $this->call('POST', '/api/stripe/webhook', [], [], [], $header, $payload);
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $this->postJson('/api/stripe/webhook', ['type' => 'checkout.session.completed'], [
            'stripe-signature' => 't=1,v1=invalid',
        ])->assertStatus(400);
    }

    public function test_checkout_completed_creates_purchase_and_token(): void
    {
        $user = User::factory()->create();
        $tool = Tool::factory()->create(['device_limit' => 2]);

        $this->postWebhook([
            'id' => 'evt_1',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_1',
                    'client_reference_id' => (string) $user->id,
                    'metadata' => [
                        'tool_id' => (string) $tool->id,
                        'user_id' => (string) $user->id,
                    ],
                    'amount_total' => 1999,
                    'payment_intent' => 'pi_test_1',
                    'subscription' => null,
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('purchases', [
            'user_id' => $user->id,
            'tool_id' => $tool->id,
            'stripe_payment_id' => 'pi_test_1',
            'amount' => 19.99,
            'status' => Purchase::STATUS_ACTIVE,
        ]);

        $purchase = Purchase::where('stripe_payment_id', 'pi_test_1')->first();
        $token = LicenseToken::where('purchase_id', $purchase->id)->first();

        $this->assertNotNull($token);
        $this->assertSame(2, $token->device_limit);
        $this->assertTrue($token->is_active);
    }

    public function test_duplicate_checkout_event_is_idempotent(): void
    {
        $user = User::factory()->create();
        $tool = Tool::factory()->create();
        $event = [
            'id' => 'evt_1',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_1',
                    'client_reference_id' => (string) $user->id,
                    'metadata' => ['tool_id' => (string) $tool->id, 'user_id' => (string) $user->id],
                    'amount_total' => 1999,
                    'payment_intent' => 'pi_test_1',
                    'subscription' => null,
                ],
            ],
        ];

        $this->postWebhook($event)->assertOk();
        $this->postWebhook($event)->assertOk();

        $this->assertDatabaseCount('purchases', 1);
        $this->assertDatabaseCount('license_tokens', 1);
    }

    public function test_subscription_checkout_stores_subscription_id(): void
    {
        $user = User::factory()->create();
        $tool = Tool::factory()->create();

        $this->postWebhook([
            'id' => 'evt_1',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_1',
                    'client_reference_id' => (string) $user->id,
                    'metadata' => ['tool_id' => (string) $tool->id, 'user_id' => (string) $user->id],
                    'amount_total' => 999,
                    'payment_intent' => null,
                    'subscription' => 'sub_test_1',
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('purchases', [
            'stripe_subscription_id' => 'sub_test_1',
            'status' => Purchase::STATUS_ACTIVE,
        ]);
    }

    public function test_invoice_payment_succeeded_renews_subscription(): void
    {
        $purchase = Purchase::factory()->create([
            'stripe_subscription_id' => 'sub_test_1',
            'status' => Purchase::STATUS_PAYMENT_FAILED,
        ]);
        $token = LicenseToken::factory()->create(['purchase_id' => $purchase->id, 'is_active' => false]);

        $this->postWebhook([
            'id' => 'evt_2',
            'type' => 'invoice.payment_succeeded',
            'data' => [
                'object' => [
                    'id' => 'in_test_1',
                    'subscription' => 'sub_test_1',
                    'lines' => [
                        'data' => [
                            ['period' => ['end' => 1760000000]],
                        ],
                    ],
                ],
            ],
        ])->assertOk();

        $purchase->refresh();
        $token->refresh();

        $this->assertSame(Purchase::STATUS_ACTIVE, $purchase->status);
        $this->assertNotNull($purchase->expires_at);
        $this->assertSame(1760000000, $purchase->expires_at->timestamp);
        $this->assertTrue($token->is_active);
    }

    public function test_invoice_payment_failed_marks_subscription_payment_failed(): void
    {
        Purchase::factory()->create([
            'stripe_subscription_id' => 'sub_test_1',
            'status' => Purchase::STATUS_ACTIVE,
        ]);

        $this->postWebhook([
            'id' => 'evt_3',
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'in_test_2',
                    'subscription' => 'sub_test_1',
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('purchases', [
            'stripe_subscription_id' => 'sub_test_1',
            'status' => Purchase::STATUS_PAYMENT_FAILED,
        ]);
    }

    public function test_subscription_deleted_expires_purchase_and_deactivates_token(): void
    {
        $purchase = Purchase::factory()->create([
            'stripe_subscription_id' => 'sub_test_1',
            'status' => Purchase::STATUS_ACTIVE,
        ]);
        $token = LicenseToken::factory()->create(['purchase_id' => $purchase->id]);

        $this->postWebhook([
            'id' => 'evt_4',
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'id' => 'sub_test_1',
                ],
            ],
        ])->assertOk();

        $purchase->refresh();
        $token->refresh();

        $this->assertSame(Purchase::STATUS_EXPIRED, $purchase->status);
        $this->assertFalse($token->is_active);
    }
}
