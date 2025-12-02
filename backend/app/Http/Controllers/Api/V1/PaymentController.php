<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\LicenseResource;
use App\Models\Customer;
use App\Models\License;
use App\Models\Payment;
use App\Models\Product;
use App\Services\StripePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    protected StripePaymentService $stripeService;

    public function __construct(StripePaymentService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    public function index(Request $request)
    {
        $query = Payment::with(['customer', 'license']);

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->get('customer_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $payments = $query->orderByDesc('created_at')->paginate(25);

        return response()->json($payments);
    }

    public function show(Payment $payment)
    {
        $payment->load(['customer', 'license']);

        return response()->json($payment);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:3', 'default:USD'],
            'payment_method' => ['required', 'string', 'max:50'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'license_type' => ['nullable', 'string', 'max:50'],
            'max_activations' => ['nullable', 'integer', 'min:1', 'default:1'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $payment = Payment::create([
            'customer_id' => $data['customer_id'],
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'USD',
            'payment_method' => $data['payment_method'],
            'transaction_id' => $data['transaction_id'] ?? Str::random(32),
            'status' => 'pending',
        ]);

        // If payment is successful, create license
        if ($request->input('status') === 'completed' || $request->input('auto_create_license')) {
            $product = Product::findOrFail($data['product_id']);
            $licenseKeyGenerator = app(\App\Services\LicenseKeyGenerator::class);

            $license = License::create([
                'license_key' => $licenseKeyGenerator->generateForType($product),
                'product_id' => $data['product_id'],
                'customer_id' => $data['customer_id'],
                'license_type' => $data['license_type'] ?? 'single_site',
                'max_activations' => $data['max_activations'] ?? 1,
                'status' => 'active',
                'purchased_at' => now(),
                'expires_at' => $data['expires_at'] ?? null,
            ]);

            $payment->license_id = $license->id;
            $payment->status = 'completed';
            $payment->paid_at = now();
            $payment->save();

            $license->load(['product', 'customer']);
            $payment->load(['customer', 'license']);

            // Send payment confirmation email via queue
            if ($payment->customer && $payment->customer->email) {
                \App\Jobs\SendEmailJob::dispatch(
                    new \App\Mail\PaymentConfirmationMail($payment),
                    $payment->customer->email
                );
            }

            return response()->json([
                'payment' => $payment,
                'license' => new LicenseResource($license),
            ], 201);
        }

        return response()->json($payment->load(['customer']), 201);
    }

    public function update(Request $request, Payment $payment)
    {
        $data = $request->validate([
            'status' => ['sometimes', 'string', 'in:pending,completed,failed,refunded'],
            'transaction_id' => ['sometimes', 'string', 'max:255'],
            'paid_at' => ['nullable', 'date'],
        ]);

        $payment->update($data);
        $payment->load(['customer', 'license']);

        // Send payment confirmation email if status changed to completed
        if (isset($data['status']) && $data['status'] === 'completed' && $payment->customer && $payment->customer->email) {
            \App\Jobs\SendEmailJob::dispatch(
                new \App\Mail\PaymentConfirmationMail($payment),
                $payment->customer->email
            );
        }

        return response()->json($payment);
    }

    /**
     * Handle payment gateway webhooks
     */
    public function webhook(Request $request, string $gateway)
    {
        $payload = $request->all();

        // Log webhook for debugging
        \Illuminate\Support\Facades\Log::info("Payment webhook received from {$gateway}", $payload);

        // Handle Stripe webhooks
        if ($gateway === 'stripe') {
            // Verify webhook signature in production
            if (config('services.stripe.webhook_secret')) {
                $signature = $request->header('Stripe-Signature');
                $payloadString = $request->getContent();
                
                if (!$this->stripeService->verifyWebhookSignature($payloadString, $signature, config('services.stripe.webhook_secret'))) {
                    return response()->json(['error' => 'Invalid signature'], 401);
                }
            }

            $result = $this->stripeService->handleWebhook($payload);
            
            // If payment succeeded and no license exists, create one
            if ($result['status'] === 'success' && isset($result['payment'])) {
                $payment = $result['payment'];
                
                if ($payment->status === 'completed' && !$payment->license_id && $payment->customer_id) {
                    // Get product_id from payment metadata or request
                    $productId = $payload['data']['object']['metadata']['product_id'] ?? null;
                    
                    if ($productId) {
                        $product = Product::find($productId);
                        if ($product) {
                            $licenseKeyGenerator = app(\App\Services\LicenseKeyGenerator::class);
                            
                            $license = License::create([
                                'license_key' => $licenseKeyGenerator->generateForType($product),
                                'product_id' => $productId,
                                'customer_id' => $payment->customer_id,
                                'license_type' => $payload['data']['object']['metadata']['license_type'] ?? 'single_site',
                                'max_activations' => (int) ($payload['data']['object']['metadata']['max_activations'] ?? 1),
                                'status' => 'active',
                                'purchased_at' => now(),
                            ]);
                            
                            $payment->license_id = $license->id;
                            $payment->save();
                        }
                    }
                }
            }
            
            return response()->json($result);
        }

        // Handle other gateways (PayPal, etc.) - generic handler
        $transactionId = $payload['transaction_id'] ?? $payload['id'] ?? null;

        if (!$transactionId) {
            return response()->json(['error' => 'Transaction ID not found'], 400);
        }

        $payment = Payment::where('transaction_id', $transactionId)->first();

        if (!$payment) {
            return response()->json(['error' => 'Payment not found'], 404);
        }

        // Update payment status based on webhook
        $status = match ($payload['status'] ?? $payload['state'] ?? '') {
            'succeeded', 'completed', 'paid' => 'completed',
            'failed', 'declined' => 'failed',
            'refunded' => 'refunded',
            default => 'pending',
        };

        $payment->status = $status;
        if ($status === 'completed' && !$payment->paid_at) {
            $payment->paid_at = now();
        }
        $payment->save();
        $payment->load(['customer', 'license']);

        // Send payment confirmation email if payment completed
        if ($status === 'completed' && $payment->customer && $payment->customer->email) {
            \App\Jobs\SendEmailJob::dispatch(
                new \App\Mail\PaymentConfirmationMail($payment),
                $payment->customer->email
            );
        }

        return response()->json(['success' => true, 'payment' => $payment]);
    }
}

