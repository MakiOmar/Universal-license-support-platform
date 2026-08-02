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

        $isRecurring = $tier->isRecurring();

        if ($tier->stripe_price_id) {
            $lineItem = ['price' => $tier->stripe_price_id, 'quantity' => 1];
        } else {
            $priceData = [
                'currency' => strtolower($tier->currency),
                'unit_amount' => (int) round($tier->price * 100),
                'product_data' => [
                    'name' => $tier->product->name.' — '.$tier->name,
                ],
            ];

            if ($isRecurring) {
                $priceData['recurring'] = [
                    'interval' => $tier->billing_cycle === PricingTier::BILLING_MONTHLY ? 'month' : 'year',
                ];
            }

            $lineItem = [
                'price_data' => $priceData,
                'quantity' => 1,
            ];
        }

        $session = Session::create([
            'mode' => $isRecurring ? 'subscription' : 'payment',
            'customer_email' => $customer->email,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'line_items' => [$lineItem],
            'metadata' => [
                'payment_id' => (string) $payment->id,
                'customer_id' => (string) $customer->id,
                'pricing_tier_id' => (string) $tier->id,
                'product_id' => (string) $tier->product_id,
                'billing_cycle' => (string) $tier->billing_cycle,
            ],
        ]);

        $payment->update([
            'gateway_reference' => $session->id,
            'meta' => array_merge($payment->meta ?? [], [
                'stripe_session' => $session->id,
            ]),
        ]);

        return $session;
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

        $this->dispatchEvent($event->type, $event->data->object);
    }

    /**
     * Process a verified Stripe event payload (also used by tests).
     */
    public function dispatchEvent(string $type, object $payload): void
    {
        match ($type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($payload),
            'invoice.paid' => $this->handleInvoicePaid($payload),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($payload),
            'charge.refunded' => $this->handleChargeRefunded($payload),
            default => null,
        };
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

            $meta = array_merge($payment->meta ?? [], [
                'stripe_session' => $session->id ?? null,
                'stripe_subscription_id' => is_string($session->subscription ?? null)
                    ? $session->subscription
                    : ($session->subscription->id ?? null),
                'stripe_customer_id' => is_string($session->customer ?? null)
                    ? $session->customer
                    : ($session->customer->id ?? null),
                'stripe_payment_intent' => is_string($session->payment_intent ?? null)
                    ? $session->payment_intent
                    : ($session->payment_intent->id ?? null),
            ]);

            $payment->update([
                'license_id' => $license->id,
                'gateway_reference' => $session->id ?? $payment->gateway_reference,
                'status' => Payment::STATUS_COMPLETED,
                'paid_at' => now(),
                'meta' => $meta,
            ]);
        });
    }

    protected function handleInvoicePaid(object $invoice): void
    {
        $subscriptionId = is_string($invoice->subscription ?? null)
            ? $invoice->subscription
            : ($invoice->subscription->id ?? null);

        // Skip the first invoice tied to checkout; license is issued on checkout.session.completed.
        if (($invoice->billing_reason ?? null) === 'subscription_create') {
            return;
        }

        if (! $subscriptionId) {
            return;
        }

        $payment = Payment::query()
            ->where('status', Payment::STATUS_COMPLETED)
            ->whereNotNull('license_id')
            ->where('meta->stripe_subscription_id', $subscriptionId)
            ->latest('id')
            ->first();

        if (! $payment?->license) {
            return;
        }

        $this->licenseService->renew($payment->license->load('pricingTier'));
    }

    protected function handleSubscriptionDeleted(object $subscription): void
    {
        $subscriptionId = $subscription->id ?? null;

        if (! $subscriptionId) {
            return;
        }

        $payment = Payment::query()
            ->whereNotNull('license_id')
            ->where('meta->stripe_subscription_id', $subscriptionId)
            ->latest('id')
            ->first();

        if ($payment?->license) {
            $this->licenseService->suspend($payment->license);
        }
    }

    protected function handleChargeRefunded(object $charge): void
    {
        $paymentIntent = is_string($charge->payment_intent ?? null)
            ? $charge->payment_intent
            : ($charge->payment_intent->id ?? null);

        $payment = null;

        if ($paymentIntent) {
            $payment = Payment::query()
                ->where('meta->stripe_payment_intent', $paymentIntent)
                ->latest('id')
                ->first();
        }

        if (! $payment && isset($charge->id)) {
            $payment = Payment::query()
                ->where('meta->stripe_charge_id', $charge->id)
                ->latest('id')
                ->first();
        }

        if (! $payment) {
            return;
        }

        $payment->update(['status' => Payment::STATUS_REFUNDED]);

        if ($payment->license) {
            $this->licenseService->suspend($payment->license);
        }
    }
}
