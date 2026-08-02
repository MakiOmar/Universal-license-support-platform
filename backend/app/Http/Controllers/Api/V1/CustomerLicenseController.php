<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\LicenseActivationResource;
use App\Http\Resources\Api\V1\LicenseResource;
use App\Models\License;
use App\Models\LicenseActivation;
use App\Services\LicenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerLicenseController extends Controller
{
    public function __construct(
        protected LicenseService $licenseService,
    ) {}

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

    public function activations(Request $request, License $license): AnonymousResourceCollection
    {
        $this->authorize('manageActivations', $license);

        $activations = $license->activations()->latest('activated_at')->get();

        return LicenseActivationResource::collection($activations);
    }

    public function deactivateActivation(
        Request $request,
        License $license,
        LicenseActivation $activation,
    ): JsonResponse {
        $this->authorize('manageActivations', $license);

        abort_unless($activation->license_id === $license->id, 404);

        $deactivated = $this->licenseService->deactivate(
            $license->license_key,
            $activation->activation_hash,
        );

        if (! $deactivated) {
            return response()->json(['message' => 'Activation not found or already inactive.'], 404);
        }

        return response()->json(['message' => 'Device removed from this license.']);
    }
}
