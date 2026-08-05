<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\License\ActivateLicenseRequest;
use App\Http\Requests\Api\V1\License\DeactivateLicenseRequest;
use App\Http\Requests\Api\V1\License\StartTrialRequest;
use App\Http\Requests\Api\V1\License\ValidateLicenseRequest;
use App\Http\Resources\Api\V1\LicenseActivationResource;
use App\Http\Resources\Api\V1\LicenseResource;
use App\Models\License;
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
        $apiKey = $request->attributes->get('api_key');

        $result = $this->licenseService->validate(
            $request->validated('license_key'),
            $request->validated('activation_type'),
            $request->validated('activation_value'),
            $apiKey?->product_id,
            [
                'device_name' => $request->validated('device_name'),
                'platform' => $request->validated('platform'),
                'app_version' => $request->validated('app_version'),
            ],
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

    public function startTrial(StartTrialRequest $request): JsonResponse
    {
        /** @var \App\Models\ApiKey $apiKey */
        $apiKey = $request->attributes->get('api_key');

        $result = $this->licenseService->startTrial(
            $apiKey,
            $request->validated('activation_type'),
            $request->validated('activation_value'),
            $request->ip(),
            $request->userAgent(),
            [
                'device_name' => $request->validated('device_name'),
                'platform' => $request->validated('platform'),
                'app_version' => $request->validated('app_version'),
            ],
        );

        return response()->json([
            'license' => new LicenseResource($result['license']),
            'activation' => new LicenseActivationResource($result['activation']),
            'expires_at' => $result['license']->expires_at,
        ], 201);
    }

    public function activate(ActivateLicenseRequest $request): LicenseActivationResource
    {
        $apiKey = $request->attributes->get('api_key');

        $activation = $this->licenseService->activate(
            $request->validated('license_key'),
            $request->validated('activation_type'),
            $request->validated('activation_value'),
            $request->ip(),
            $request->userAgent(),
            $apiKey?->product_id,
            (bool) $request->boolean('replace_oldest'),
            [
                'device_name' => $request->validated('device_name'),
                'platform' => $request->validated('platform'),
                'app_version' => $request->validated('app_version'),
            ],
        );

        return new LicenseActivationResource($activation);
    }

    public function deactivate(DeactivateLicenseRequest $request): JsonResponse
    {
        $apiKey = $request->attributes->get('api_key');
        $licenseKey = $request->validated('license_key');

        if ($apiKey?->product_id) {
            $ownsProduct = License::query()
                ->where('license_key', $licenseKey)
                ->where('product_id', $apiKey->product_id)
                ->exists();

            if (! $ownsProduct) {
                return response()->json(['message' => 'This license does not belong to this product.'], 422);
            }
        }

        $deactivated = $this->licenseService->deactivate(
            $licenseKey,
            $request->validated('activation_hash'),
        );

        if (! $deactivated) {
            return response()->json(['message' => 'Activation not found.'], 404);
        }

        return response()->json(['message' => 'Activation deactivated.']);
    }

    public function activations(Request $request, string $licenseKey): JsonResponse
    {
        $apiKey = $request->attributes->get('api_key');

        $query = License::with('activations')->where('license_key', $licenseKey);

        if ($apiKey?->product_id) {
            $query->where('product_id', $apiKey->product_id);
        }

        $license = $query->firstOrFail();

        return response()->json([
            'license_key' => $license->license_key,
            'activations' => LicenseActivationResource::collection($license->activations),
        ]);
    }
}
