<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\LicenseResource;
use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerLicenseController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', License::class);

        $licenses = $request->user()
            ->licenses()
            ->with(['product', 'pricingTier', 'activations'])
            ->latest()
            ->get();

        return LicenseResource::collection($licenses);
    }

    public function show(Request $request, License $license): LicenseResource
    {
        $this->authorize('view', $license);

        $license->load(['product', 'pricingTier', 'activations']);

        return new LicenseResource($license);
    }
}
