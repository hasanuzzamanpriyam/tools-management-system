<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Tool;
use App\Models\User;
use App\Services\CreditsService;
use App\Services\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('stripe-signature');

        try {
            $event = Webhook::constructEvent($payload, $signature, config('services.stripe.webhook_secret'));
        } catch (\UnexpectedValueException $e) {
            return response()->json(['message' => 'Invalid payload.'], 400);
        } catch (SignatureVerificationException $e) {
            return response()->json(['message' => 'Invalid signature.'], 400);
        }

        match ($event->type) {
            Event::CHECKOUT_SESSION_COMPLETED => $this->handleCheckoutCompleted($event->data->object),
            Event::INVOICE_PAYMENT_SUCCEEDED => $this->handleInvoicePaymentSucceeded($event->data->object),
            Event::INVOICE_PAYMENT_FAILED => $this->handleInvoicePaymentFailed($event->data->object),
            Event::CUSTOMER_SUBSCRIPTION_DELETED => $this->handleSubscriptionDeleted($event->data->object),
            default => null,
        };

        return response()->json(['received' => true]);
    }

    private function handleCheckoutCompleted(object $session): void
    {
        $user = User::find($session->client_reference_id ?? $session->metadata->user_id ?? null);
        $tool = Tool::find($session->metadata->tool_id ?? null);

        if (! $user || ! $tool) {
            return;
        }

        $paymentId = $session->payment_intent ?? $session->id;

        if (Purchase::where('stripe_payment_id', $paymentId)->exists()) {
            return;
        }

        $purchase = Purchase::create([
            'user_id' => $user->id,
            'tool_id' => $tool->id,
            'stripe_payment_id' => $paymentId,
            'stripe_subscription_id' => $session->subscription ?? null,
            'amount' => ($session->amount_total ?? $tool->price * 100) / 100,
            'status' => Purchase::STATUS_ACTIVE,
        ]);

        (new TokenService)->generate($purchase);
        (new CreditsService)->creditFirstPurchase($purchase);
    }

    private function handleInvoicePaymentSucceeded(object $invoice): void
    {
        $purchase = Purchase::query()
            ->where('stripe_subscription_id', $invoice->subscription)
            ->first();

        if (! $purchase) {
            return;
        }

        $periodEnd = data_get($invoice->lines->data, '0.period.end');

        $purchase->update([
            'status' => Purchase::STATUS_ACTIVE,
            'expires_at' => $periodEnd ? now()->timestamp($periodEnd) : null,
        ]);

        $purchase->licenseToken?->update(['is_active' => true]);
    }

    private function handleInvoicePaymentFailed(object $invoice): void
    {
        $purchase = Purchase::query()
            ->where('stripe_subscription_id', $invoice->subscription)
            ->first();

        if ($purchase) {
            $purchase->update(['status' => Purchase::STATUS_PAYMENT_FAILED]);
        }
    }

    private function handleSubscriptionDeleted(object $subscription): void
    {
        $purchase = Purchase::query()
            ->where('stripe_subscription_id', $subscription->id)
            ->first();

        if ($purchase) {
            $purchase->update(['status' => Purchase::STATUS_EXPIRED]);
            $purchase->licenseToken?->update(['is_active' => false]);
        }
    }
}
