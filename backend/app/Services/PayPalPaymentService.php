<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use PaypalServerSdkLib\Environment;
use PaypalServerSdkLib\PaypalServerSdkClient;
use PaypalServerSdkLib\PaypalServerSdkClientBuilder;
use PaypalServerSdkLib\Authentication\ClientCredentialsAuthCredentialsBuilder;
use PaypalServerSdkLib\Controllers\OrdersController;
use PaypalServerSdkLib\Models\OrderRequest;
use PaypalServerSdkLib\Models\Money;
use PaypalServerSdkLib\Models\PurchaseUnitRequest;
use PaypalServerSdkLib\Models\ApplicationContext;

class PayPalPaymentService
{
    protected PaypalServerSdkClient $client;

    public function __construct()
    {
        $clientId = config('services.paypal.client_id');
        $clientSecret = config('services.paypal.client_secret');
        $isProduction = config('services.paypal.mode') === 'production';

        $this->client = PaypalServerSdkClientBuilder::init()
            ->clientCredentialsAuthCredentials(
                ClientCredentialsAuthCredentialsBuilder::init(
                    $clientId,
                    $clientSecret
                )
            )
            ->environment($isProduction ? Environment::PRODUCTION : Environment::SANDBOX)
            ->build();
    }

    /**
     * Create a PayPal order for a product purchase
     * 
     * @param Payment $payment
     * @param Product $product
     * @param array $metadata Additional metadata
     * @return array
     */
    public function createOrder(Payment $payment, Product $product, array $metadata = []): array
    {
        try {
            $ordersController = $this->client->getOrdersController();

            // Create purchase unit
            $amount = Money::fromArray([
                'currency_code' => strtoupper($payment->currency ?? 'USD'),
                'value' => number_format($payment->amount, 2, '.', ''),
            ]);

            $purchaseUnit = PurchaseUnitRequest::fromArray([
                'reference_id' => "payment_{$payment->id}",
                'description' => "Purchase: {$product->name}",
                'custom_id' => (string) $payment->id,
                'amount' => $amount,
            ]);

            // Create application context
            $applicationContext = ApplicationContext::fromArray([
                'brand_name' => config('app.name'),
                'landing_page' => 'BILLING',
                'user_action' => 'PAY_NOW',
                'return_url' => config('services.paypal.return_url'),
                'cancel_url' => config('services.paypal.cancel_url'),
            ]);

            // Create order request
            $orderRequest = OrderRequest::fromArray([
                'intent' => 'CAPTURE',
                'purchase_units' => [$purchaseUnit],
                'application_context' => $applicationContext,
            ]);

            $response = $ordersController->createOrder([
                'body' => $orderRequest,
                'prefer' => 'return=representation',
            ]);

            $order = $response->getResult();

            // Update payment with PayPal order ID
            $payment->update([
                'transaction_id' => $order->getId(),
            ]);

            // Find approval URL
            $approvalUrl = null;
            $links = $order->getLinks();
            if ($links) {
                foreach ($links as $link) {
                    if ($link->getRel() === 'approve') {
                        $approvalUrl = $link->getHref();
                        break;
                    }
                }
            }

            return [
                'id' => $order->getId(),
                'status' => $order->getStatus(),
                'approval_url' => $approvalUrl,
                'order' => $order,
            ];
        } catch (\Exception $e) {
            Log::error('PayPal order creation failed', [
                'error' => $e->getMessage(),
                'payment_id' => $payment->id,
            ]);
            throw $e;
        }
    }

    /**
     * Capture a PayPal order (after customer approval)
     * 
     * @param string $orderId
     * @return array
     */
    public function captureOrder(string $orderId): array
    {
        try {
            $ordersController = $this->client->getOrdersController();

            $response = $ordersController->ordersCapture([
                'id' => $orderId,
                'prefer' => 'return=representation',
            ]);

            $order = $response->getResult();

            return [
                'id' => $order->getId(),
                'status' => $order->getStatus(),
                'order' => $order,
            ];
        } catch (\Exception $e) {
            Log::error('PayPal order capture failed', [
                'error' => $e->getMessage(),
                'order_id' => $orderId,
            ]);
            throw $e;
        }
    }

    /**
     * Retrieve a PayPal order
     * 
     * @param string $orderId
     * @return array
     */
    public function getOrder(string $orderId): array
    {
        try {
            $ordersController = $this->client->getOrdersController();

            $response = $ordersController->ordersGet([
                'id' => $orderId,
            ]);

            $order = $response->getResult();

            return [
                'id' => $order->getId(),
                'status' => $order->getStatus(),
                'order' => $order,
            ];
        } catch (\Exception $e) {
            Log::error('PayPal order retrieval failed', [
                'error' => $e->getMessage(),
                'order_id' => $orderId,
            ]);
            throw $e;
        }
    }

    /**
     * Handle PayPal webhook event
     * 
     * @param array $payload
     * @return array
     */
    public function handleWebhook(array $payload): array
    {
        $eventType = $payload['event_type'] ?? null;
        $resource = $payload['resource'] ?? [];

        Log::info("PayPal webhook event: {$eventType}", $resource);

        switch ($eventType) {
            case 'PAYMENT.CAPTURE.COMPLETED':
                return $this->handlePaymentCompleted($resource);
            case 'PAYMENT.CAPTURE.DENIED':
            case 'PAYMENT.CAPTURE.REFUNDED':
                return $this->handlePaymentFailed($resource);
            case 'PAYMENT.CAPTURE.REFUNDED':
                return $this->handleRefund($resource);
            default:
                Log::info("Unhandled PayPal webhook event: {$eventType}");
                return ['status' => 'ignored', 'message' => "Event type {$eventType} not handled"];
        }
    }

    /**
     * Handle completed payment
     */
    protected function handlePaymentCompleted(array $resource): array
    {
        $orderId = $resource['supplementary_data']['related_ids']['order_id'] ?? null;
        $captureId = $resource['id'] ?? null;
        
        if (!$orderId) {
            return ['status' => 'error', 'message' => 'Order ID not found'];
        }

        $payment = Payment::where('transaction_id', $orderId)->first();

        if (!$payment) {
            Log::warning("Payment not found for PayPal order: {$orderId}");
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
    protected function handlePaymentFailed(array $resource): array
    {
        $orderId = $resource['supplementary_data']['related_ids']['order_id'] ?? null;
        
        if (!$orderId) {
            return ['status' => 'error', 'message' => 'Order ID not found'];
        }

        $payment = Payment::where('transaction_id', $orderId)->first();

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
    protected function handleRefund(array $resource): array
    {
        $orderId = $resource['supplementary_data']['related_ids']['order_id'] ?? null;
        
        if (!$orderId) {
            return ['status' => 'error', 'message' => 'Order ID not found'];
        }

        $payment = Payment::where('transaction_id', $orderId)->first();

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
     * @param array $headers
     * @return bool
     */
    public function verifyWebhookSignature(string $payload, array $headers): bool
    {
        // PayPal webhook verification requires specific headers
        // This is a simplified version - in production, use PayPal's webhook verification API
        $webhookId = config('services.paypal.webhook_id');
        
        if (!$webhookId) {
            Log::warning('PayPal webhook ID not configured, skipping verification');
            return true; // Allow in development
        }

        // In production, verify using PayPal's webhook verification endpoint
        // For now, we'll log and allow (should be implemented properly in production)
        Log::info('PayPal webhook signature verification', [
            'webhook_id' => $webhookId,
            'headers' => $headers,
        ]);

        return true; // TODO: Implement proper signature verification
    }
}
