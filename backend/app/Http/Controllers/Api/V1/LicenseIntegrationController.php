<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\License\ActivateLicenseRequest;
use App\Http\Requests\Api\V1\License\DeactivateLicenseRequest;
use App\Http\Requests\Api\V1\License\ValidateLicenseRequest;
use App\Http\Resources\Api\V1\LicenseActivationResource;
use App\Http\Resources\Api\V1\LicenseResource;
use App\Models\License;
use App\Models\LicenseActivation;
use App\Services\LicenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LicenseIntegrationController extends Controller
{
    public function __construct(
        protected LicenseService $licenseService,
    ) {}

    public function validate(ValidateLicenseRequest $request): JsonResponse
    {
        $result = $this->licenseService->validate(
            $request->validated('license_key'),
            $request->validated('activation_type'),
            $request->validated('activation_value'),
        );

        $response = [
            'valid' => $result['valid'] ?? false,
            'reason' => $result['reason'] ?? null,
        ];

        if (isset($result['license'])) {
            $response['license'] = new LicenseResource($result['license']->load(['product']));
            $response['activation_valid'] = $result['activation_valid'] ?? null;
            $response['activations_used'] = $result['activations_used'] ?? null;
            $response['max_activations'] = $result['max_activations'] ?? null;
            $response['expires_at'] = $result['expires_at'] ?? null;
        }

        return response()->json($response);
    }

    public function activate(ActivateLicenseRequest $request): LicenseActivationResource
    {
        $activation = $this->licenseService->activate(
            $request->validated('license_key'),
            $request->validated('activation_type'),
            $request->validated('activation_value'),
            $request->ip(),
            $request->userAgent(),
        );

        return new LicenseActivationResource($activation);
    }

    public function deactivate(DeactivateLicenseRequest $request): JsonResponse
    {
        $deactivated = $this->licenseService->deactivate(
            $request->validated('license_key'),
            $request->validated('activation_hash'),
        );

        if (! $deactivated) {
            return response()->json(['message' => 'Activation not found.'], 404);
        }

        return response()->json(['message' => 'Activation deactivated.']);
    }

    public function activations(string $licenseKey): JsonResponse
    {
        $license = License::with('activations')
            ->where('license_key', $licenseKey)
            ->firstOrFail();

        return response()->json([
            'license_key' => $license->license_key,
            'activations' => LicenseActivationResource::collection($license->activations),
        ]);
    }
}
