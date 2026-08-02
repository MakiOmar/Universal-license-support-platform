<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Checkout\CreateCheckoutSessionRequest;
use App\Models\PricingTier;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
    ) {}

    public function create(CreateCheckoutSessionRequest $request): JsonResponse
    {
        if (! filled(config('services.stripe.secret'))) {
            return response()->json([
                'message' => 'Stripe is not configured. Set STRIPE_SECRET in the backend environment.',
            ], 503);
        }

        $tier = PricingTier::with('product')
            ->where('is_active', true)
            ->findOrFail($request->validated('pricing_tier_id'));

        $session = $this->paymentService->createCheckoutSession(
            $request->user(),
            $tier,
            $request->only(['success_url', 'cancel_url']),
        );

        return response()->json([
            'checkout_url' => $session->url,
            'session_id' => $session->id,
        ]);
    }
}
