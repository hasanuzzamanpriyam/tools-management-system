<?php

namespace Tests\Feature;

use App\Models\Purchase;
use App\Models\ReferralCredit;
use App\Models\Tool;
use App\Models\User;
use App\Services\CreditsService;
use App\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Stripe\StripeClient;
use Tests\TestCase;

class ReferralTest extends TestCase
{
    use RefreshDatabase;

    private string $webhookSecret = 'whsec_test_1234';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.stripe.webhook_secret' => $this->webhookSecret]);
    }

    private function register(array $overrides = []): TestResponse
    {
        return $this->postJson('/api/register', array_merge([
            'name' => 'New Developer',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides));
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

    private function completedCheckoutEvent(User $buyer, Tool $tool, string $paymentId): array
    {
        return [
            'id' => 'evt_checkout_'.$paymentId,
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_'.$paymentId,
                    'client_reference_id' => (string) $buyer->id,
                    'metadata' => ['tool_id' => (string) $tool->id, 'user_id' => (string) $buyer->id],
                    'amount_total' => (int) round($tool->price * 100),
                    'payment_intent' => $paymentId,
                    'subscription' => null,
                ],
            ],
        ];
    }

    public function test_registration_with_valid_referral_code_links_referrer(): void
    {
        $referrer = User::factory()->create(['referral_code' => 'ABCD1234']);

        $response = $this->register(['referral_code' => 'ABCD1234'])->assertStatus(201);

        $newUser = User::find($response->json('user.id'));

        $this->assertSame($referrer->id, $newUser->referred_by);
        $this->assertNotNull($newUser->referral_code);
        $this->assertNotSame('ABCD1234', $newUser->referral_code);
    }

    public function test_registration_with_invalid_referral_code_is_rejected(): void
    {
        $this->register(['referral_code' => 'DOESNOTEXIST'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['referral_code']);
    }

    public function test_registration_without_referral_code_creates_standalone_user(): void
    {
        $response = $this->register()->assertStatus(201);

        $this->assertNull(User::find($response->json('user.id'))->referred_by);
    }

    public function test_referred_users_first_purchase_credits_the_referrer(): void
    {
        $referrer = User::factory()->create();
        $buyer = User::factory()->create(['referred_by' => $referrer->id]);
        $tool = Tool::factory()->create(['referral_credits' => 5.00]);

        $this->postWebhook($this->completedCheckoutEvent($buyer, $tool, 'pi_first'))->assertOk();

        $purchase = Purchase::where('stripe_payment_id', 'pi_first')->firstOrFail();

        $this->assertDatabaseHas('referral_credits', [
            'referrer_id' => $referrer->id,
            'purchase_id' => $purchase->id,
            'amount' => 5.00,
        ]);
    }

    public function test_no_credit_for_second_purchase_by_referred_user(): void
    {
        $referrer = User::factory()->create();
        $buyer = User::factory()->create(['referred_by' => $referrer->id]);
        $tool = Tool::factory()->create(['referral_credits' => 5.00]);

        $this->postWebhook($this->completedCheckoutEvent($buyer, $tool, 'pi_first'))->assertOk();
        $this->postWebhook($this->completedCheckoutEvent($buyer, $tool, 'pi_second'))->assertOk();

        $this->assertDatabaseCount('referral_credits', 1);
    }

    public function test_no_credit_without_referrer(): void
    {
        $buyer = User::factory()->create();
        $tool = Tool::factory()->create(['referral_credits' => 5.00]);

        $this->postWebhook($this->completedCheckoutEvent($buyer, $tool, 'pi_first'))->assertOk();

        $this->assertDatabaseCount('referral_credits', 0);
    }

    public function test_no_credit_when_tool_offers_none(): void
    {
        $referrer = User::factory()->create();
        $buyer = User::factory()->create(['referred_by' => $referrer->id]);
        $tool = Tool::factory()->create(['referral_credits' => 0]);

        $this->postWebhook($this->completedCheckoutEvent($buyer, $tool, 'pi_first'))->assertOk();

        $this->assertDatabaseCount('referral_credits', 0);
    }

    public function test_balance_and_history_endpoint(): void
    {
        $user = User::factory()->create();
        $purchase = Purchase::factory()->create();
        ReferralCredit::create([
            'referrer_id' => $user->id,
            'purchase_id' => $purchase->id,
            'amount' => 5.00,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/me/credits')
            ->assertOk()
            ->assertJsonCount(1, 'history')
            ->assertJsonStructure([
                'balance',
                'history' => [
                    '*' => ['id', 'amount', 'remaining', 'created_at'],
                ],
            ]);

        $this->assertEquals(5.0, $response->json('balance'));
    }

    public function test_checkout_payload_discounts_available_credits(): void
    {
        $tool = Tool::factory()->create(['pricing_model' => Tool::PRICING_ONE_TIME, 'price' => 19.99]);
        $user = User::factory()->create();

        $service = new PurchaseService($this->createMock(StripeClient::class));

        $this->assertSame(1999, $service->buildCheckoutPayload($tool, $user)['line_items'][0]['price_data']['unit_amount']);
        $this->assertSame(1499, $service->buildCheckoutPayload($tool, $user, 5.00)['line_items'][0]['price_data']['unit_amount']);
        $this->assertSame(0, $service->buildCheckoutPayload($tool, $user, 99.00)['line_items'][0]['price_data']['unit_amount']);
    }

    public function test_redeem_consumes_available_credits(): void
    {
        $user = User::factory()->create();
        $credit = ReferralCredit::create([
            'referrer_id' => $user->id,
            'purchase_id' => Purchase::factory()->create()->id,
            'amount' => 5.00,
        ]);

        $credits = new CreditsService;

        $this->assertSame(5.00, $credits->balance($user));

        $credits->redeem($user, 2.00);

        $this->assertSame(3.00, $credits->balance($user));
        $credit->refresh();
        $this->assertSame(2.00, (float) $credit->redeemed_amount);

        $credits->redeem($user, 3.00);

        $this->assertSame(0.00, $credits->balance($user));
        $credit->refresh();
        $this->assertNotNull($credit->redeemed_at);
    }

    public function test_redeem_cannot_exceed_balance(): void
    {
        $user = User::factory()->create();
        ReferralCredit::create([
            'referrer_id' => $user->id,
            'purchase_id' => Purchase::factory()->create()->id,
            'amount' => 5.00,
        ]);

        $credits = new CreditsService;

        $credits->redeem($user, 25.00);

        $this->assertSame(0.00, $credits->balance($user));
    }
}
