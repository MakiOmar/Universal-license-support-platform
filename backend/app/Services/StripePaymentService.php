<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\StripeClient;

class StripePaymentService
{
    protected StripeClient $stripe;

    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret_key'));
        $this->stripe = new StripeClient(config('services.stripe.secret_key'));
    }

    /**
     * Create a payment intent for a product purchase
     * 
     * @param Payment $payment
     * @param Product $product
     * @param array $metadata Additional metadata to attach
     * @return PaymentIntent
     * @throws ApiErrorException
     */
    public function createPaymentIntent(Payment $payment, Product $product, array $metadata = []): PaymentIntent
    {
        $metadata = array_merge([
            'payment_id' => $payment->id,
            'customer_id' => $payment->customer_id,
            'product_id' => $product->id,
            'product_name' => $product->name,
        ], $metadata);

        $paymentIntent = $this->stripe->paymentIntents->create([
            'amount' => (int) ($payment->amount * 100), // Convert to cents
            'currency' => strtolower($payment->currency ?? 'usd'),
            'metadata' => $metadata,
            'description' => "Purchase: {$product->name}",
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        ]);

        // Update payment with Stripe payment intent ID
        $payment->update([
            'transaction_id' => $paymentIntent->id,
        ]);

        return $paymentIntent;
    }

    /**
     * Retrieve a payment intent
     * 
     * @param string $paymentIntentId
     * @return PaymentIntent
     * @throws ApiErrorException
     */
    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return $this->stripe->paymentIntents->retrieve($paymentIntentId);
    }

    /**
     * Handle Stripe webhook event
     * 
     * @param array $payload
     * @return array
     */
    public function handleWebhook(array $payload): array
    {
        $eventType = $payload['type'] ?? null;
        $data = $payload['data']['object'] ?? [];

        Log::info("Stripe webhook event: {$eventType}", $data);

        switch ($eventType) {
            case 'payment_intent.succeeded':
                return $this->handlePaymentSucceeded($data);
            case 'payment_intent.payment_failed':
                return $this->handlePaymentFailed($data);
            case 'charge.refunded':
                return $this->handleRefund($data);
            default:
                Log::info("Unhandled Stripe webhook event: {$eventType}");
                return ['status' => 'ignored', 'message' => "Event type {$eventType} not handled"];
        }
    }

    /**
     * Handle successful payment
     */
    protected function handlePaymentSucceeded(array $data): array
    {
        $paymentIntentId = $data['id'] ?? null;
        
        if (!$paymentIntentId) {
            return ['status' => 'error', 'message' => 'Payment intent ID not found'];
        }

        $payment = Payment::where('transaction_id', $paymentIntentId)->first();

        if (!$payment) {
            Log::warning("Payment not found for Stripe payment intent: {$paymentIntentId}");
            return ['status' => 'error', 'message' => 'Payment not found'];
        }

        $payment->update([
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        $payment->load(['customer', 'license']);

        // Send payment confirmation email
        if ($payment->customer && $payment->customer->email) {
            \App\Jobs\SendEmailJob::dispatch(
                new \App\Mail\PaymentConfirmationMail($payment),
                $payment->customer->email
            );
        }

        return ['status' => 'success', 'payment' => $payment];
    }

    /**
     * Handle failed payment
     */
    protected function handlePaymentFailed(array $data): array
    {
        $paymentIntentId = $data['id'] ?? null;
        
        if (!$paymentIntentId) {
            return ['status' => 'error', 'message' => 'Payment intent ID not found'];
        }

        $payment = Payment::where('transaction_id', $paymentIntentId)->first();

        if ($payment) {
            $payment->update([
                'status' => 'failed',
            ]);
        }

        return ['status' => 'success', 'payment' => $payment];
    }

    /**
     * Handle refund
     */
    protected function handleRefund(array $data): array
    {
        $chargeId = $data['id'] ?? null;
        $paymentIntentId = $data['payment_intent'] ?? null;
        
        if (!$paymentIntentId) {
            return ['status' => 'error', 'message' => 'Payment intent ID not found'];
        }

        $payment = Payment::where('transaction_id', $paymentIntentId)->first();

        if ($payment) {
            $payment->update([
                'status' => 'refunded',
            ]);
        }

        return ['status' => 'success', 'payment' => $payment];
    }

    /**
     * Verify webhook signature (for production use)
     * 
     * @param string $payload
     * @param string $signature
     * @param string $secret
     * @return bool
     */
    public function verifyWebhookSignature(string $payload, string $signature, string $secret): bool
    {
        try {
            \Stripe\Webhook::constructEvent($payload, $signature, $secret);
            return true;
        } catch (\Exception $e) {
            Log::error('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}

