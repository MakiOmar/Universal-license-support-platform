<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\License;
use App\Models\Payment;
use App\Models\PricingTier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;
use UnexpectedValueException;

class PaymentService
{
    public function __construct(
        protected LicenseService $licenseService,
    ) {}

    public function createCheckoutSession(Customer $customer, PricingTier $tier, array $options = []): Session
    {
        $secret = config('services.stripe.secret');
        if (! filled($secret)) {
            throw new UnexpectedValueException('Stripe secret is not configured.');
        }

        Stripe::setApiKey($secret);
        $tier->loadMissing('product');

        $successUrl = $options['success_url'] ?? config('services.stripe.checkout_success_url');
        $cancelUrl = $options['cancel_url'] ?? config('services.stripe.checkout_cancel_url');

        $payment = Payment::create([
            'customer_id' => $customer->id,
            'pricing_tier_id' => $tier->id,
            'amount' => $tier->price,
            'currency' => $tier->currency,
            'gateway' => 'stripe',
            'status' => Payment::STATUS_PENDING,
        ]);

        $lineItem = $tier->stripe_price_id
            ? ['price' => $tier->stripe_price_id, 'quantity' => 1]
            : [
                'price_data' => [
                    'currency' => strtolower($tier->currency),
                    'unit_amount' => (int) round($tier->price * 100),
                    'product_data' => [
                        'name' => $tier->product->name.' — '.$tier->name,
                    ],
                ],
                'quantity' => 1,
            ];

        return Session::create([
            'mode' => 'payment',
            'customer_email' => $customer->email,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'line_items' => [$lineItem],
            'metadata' => [
                'payment_id' => (string) $payment->id,
                'customer_id' => (string) $customer->id,
                'pricing_tier_id' => (string) $tier->id,
                'product_id' => (string) $tier->product_id,
            ],
        ]);
    }

    public function handleWebhook(string $payload, ?string $signature): void
    {
        $secret = config('services.stripe.webhook_secret');

        if (! $secret) {
            Log::warning('Stripe webhook secret not configured.');

            return;
        }

        try {
            $event = Webhook::constructEvent($payload, $signature ?? '', $secret);
        } catch (UnexpectedValueException|SignatureVerificationException $e) {
            Log::warning('Stripe webhook verification failed.', ['error' => $e->getMessage()]);

            throw $e;
        }

        if ($event->type === 'checkout.session.completed') {
            $this->handleCheckoutCompleted($event->data->object);
        }
    }

    protected function handleCheckoutCompleted(object $session): void
    {
        $metadata = (array) ($session->metadata ?? []);
        $paymentId = $metadata['payment_id'] ?? null;

        if (! $paymentId) {
            return;
        }

        DB::transaction(function () use ($session, $paymentId) {
            $payment = Payment::lockForUpdate()->find($paymentId);

            if (! $payment || $payment->status === Payment::STATUS_COMPLETED) {
                return;
            }

            $tier = PricingTier::with('product')->find($payment->pricing_tier_id);
            $customer = Customer::find($payment->customer_id);

            if (! $tier || ! $customer) {
                return;
            }

            $license = $this->licenseService->issue($customer, $tier->product, $tier);

            $payment->update([
                'license_id' => $license->id,
                'gateway_reference' => $session->id ?? $session->payment_intent ?? null,
                'status' => Payment::STATUS_COMPLETED,
                'paid_at' => now(),
                'meta' => ['stripe_session' => $session->id ?? null],
            ]);
        });
    }
}
