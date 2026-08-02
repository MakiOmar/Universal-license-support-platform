<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerPaymentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $payments = $request->user()
            ->payments()
            ->with(['pricingTier.product', 'license'])
            ->latest()
            ->get();

        return PaymentResource::collection($payments);
    }

    public function show(Request $request, Payment $payment): PaymentResource
    {
        abort_unless($payment->customer_id === $request->user()->id, 403);

        $payment->load(['pricingTier.product', 'license']);

        return new PaymentResource($payment);
    }
}
