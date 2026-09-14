<?php

namespace App\Services;

use App\Models\Tool;
use App\Models\User;
use Stripe\Checkout\Session;
use Stripe\StripeClient;

class PurchaseService
{
    public function __construct(
        private readonly ?StripeClient $stripe,
        private readonly ?CreditsService $credits = null,
    ) {}

    /**
     * Build the Stripe Checkout Session payload for the given tool.
     *
     * @return array<string, mixed>
     */
    public function buildCheckoutPayload(Tool $tool, User $user, float $discount = 0.0): array
    {
        $priceData = [
            'currency' => 'usd',
            'unit_amount' => max((int) round($tool->price * 100) - (int) round($discount * 100), 0),
            'product_data' => ['name' => $tool->name],
        ];

        $isSubscription = $tool->pricing_model === Tool::PRICING_SUBSCRIPTION;

        if ($isSubscription) {
            $priceData['recurring'] = ['interval' => 'month'];
        }

        return [
            'mode' => $isSubscription ? 'subscription' : 'payment',
            'line_items' => [
                [
                    'price_data' => $priceData,
                    'quantity' => 1,
                ],
            ],
            'success_url' => config('app.url').'/checkout/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => config('app.url').'/checkout/cancel',
            'client_reference_id' => (string) $user->id,
            'customer_email' => $user->email,
            'metadata' => [
                'tool_id' => (string) $tool->id,
                'user_id' => (string) $user->id,
            ],
        ];
    }

    /**
     * Create a Stripe Checkout Session and return its id and hosted URL.
     *
     * @return array{id: string, url: string}
     */
    public function checkout(Tool $tool, User $user): array
    {
        if (! $this->stripe) {
            throw new \RuntimeException('Stripe is not configured.');
        }

        $discount = $this->credits ? $this->credits->balance($user) : 0.0;
        $discount = min($discount, (float) $tool->price);

        /** @var Session $session */
        $session = $this->stripe->checkout->sessions->create(
            $this->buildCheckoutPayload($tool, $user, $discount),
        );

        if ($discount > 0) {
            $this->credits?->redeem($user, $discount);
        }

        return [
            'id' => $session->id,
            'url' => $session->url,
        ];
    }
}
