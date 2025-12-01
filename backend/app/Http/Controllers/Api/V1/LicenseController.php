<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ActivateLicenseRequest;
use App\Http\Requests\Api\V1\StoreLicenseRequest;
use App\Http\Resources\Api\V1\LicenseResource;
use App\Models\License;
use App\Services\LicenseActivationService;
use App\Services\LicenseKeyGenerator;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function __construct(
        protected LicenseActivationService $activationService,
        protected LicenseKeyGenerator $keyGenerator
    ) {
    }

    public function index()
    {
        $licenses = License::with(['product', 'customer', 'activations'])
            ->paginate(25);

        return LicenseResource::collection($licenses);
    }

    public function show(License $license)
    {
        $license->load(['product', 'customer', 'activations']);

        return new LicenseResource($license);
    }

    public function store(StoreLicenseRequest $request)
    {
        $data = $request->validated();

        // Auto-generate license key if not provided
        if (empty($data['license_key'])) {
            $product = \App\Models\Product::findOrFail($data['product_id']);
            $data['license_key'] = $this->keyGenerator->generateForType($product);
        }

        // Set defaults
        $data['status'] = $data['status'] ?? 'pending';
        $data['max_activations'] = $data['max_activations'] ?? 1;
        $data['purchased_at'] = $data['purchased_at'] ?? now();

        $license = License::create($data);
        $license->load(['product', 'customer']);

        return new LicenseResource($license);
    }

    public function update(Request $request, License $license)
    {
        $data = $request->validate([
            'license_key' => ['sometimes', 'string', 'max:255', 'unique:licenses,license_key,' . $license->id],
            'product_id' => ['sometimes', 'integer', 'exists:products,id'],
            'customer_id' => ['sometimes', 'integer', 'exists:customers,id'],
            'license_type' => ['sometimes', 'string', 'max:50'],
            'max_activations' => ['nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['nullable', 'string', 'in:pending,active,expired,suspended,cancelled'],
            'purchased_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:purchased_at'],
            'support_expires_at' => ['nullable', 'date', 'after_or_equal:purchased_at'],
        ]);

        $license->update($data);
        $license->load(['product', 'customer', 'activations']);

        return new LicenseResource($license);
    }

    public function destroy(License $license)
    {
        $license->delete();

        return response()->json(null, 204);
    }

    public function validateKey(Request $request)
    {
        $data = $request->validate([
            'license_key' => ['required', 'string'],
        ]);

        $license = License::where('license_key', $data['license_key'])->first();

        if (! $license) {
            return response()->json(['valid' => false, 'reason' => 'not_found'], 404);
        }

        if ($license->status !== 'active') {
            return response()->json(['valid' => false, 'reason' => $license->status], 200);
        }

        if ($license->expires_at && $license->expires_at->isPast()) {
            return response()->json(['valid' => false, 'reason' => 'expired'], 200);
        }

        return response()->json(['valid' => true], 200);
    }

    public function activate(ActivateLicenseRequest $request)
    {
        $data = $request->validated();

        $license = License::where('license_key', $data['license_key'])->firstOrFail();

        $result = $this->activationService->activate(
            $license,
            $data['activation_type'],
            $data['activation_value'],
            $request
        );

        $statusCode = $result['success'] ? 201 : 400;

        return response()->json($result, $statusCode);
    }

    public function deactivate(Request $request)
    {
        $data = $request->validate([
            'license_key' => ['required', 'string'],
            'activation_type' => ['required', 'string', 'in:domain,machine_id,device_id,api_key'],
            'activation_value' => ['required', 'string', 'max:255'],
        ]);

        $license = License::where('license_key', $data['license_key'])->firstOrFail();

        $result = $this->activationService->deactivate(
            $license,
            $data['activation_type'],
            $data['activation_value']
        );

        $statusCode = $result['success'] ? 200 : 404;

        return response()->json($result, $statusCode);
    }

    public function transfer(Request $request, License $license)
    {
        $data = $request->validate([
            'new_customer_id' => ['required', 'integer', 'exists:customers,id'],
        ]);

        $license->customer_id = $data['new_customer_id'];
        $license->save();
        $license->load(['product', 'customer']);

        return response()->json([
            'success' => true,
            'license' => new LicenseResource($license),
        ]);
    }

    public function getActivations(Request $request, string $licenseKey)
    {
        $license = License::where('license_key', $licenseKey)->firstOrFail();
        $license->load('activations');

        return \App\Http\Resources\Api\V1\LicenseActivationResource::collection(
            $license->activations()->where('status', 'active')->get()
        );
    }

    public function checkUpdates(Request $request, string $licenseKey)
    {
        $data = $request->validate([
            'current_version' => ['nullable', 'string', 'max:50'],
        ]);

        $license = License::where('license_key', $licenseKey)
            ->with('product')
            ->firstOrFail();

        if ($license->status !== 'active') {
            return response()->json([
                'has_update' => false,
                'message' => 'License is not active.',
            ], 400);
        }

        $product = $license->product;
        $currentVersion = $data['current_version'] ?? null;
        $latestVersion = $product->version;

        $hasUpdate = $currentVersion && $latestVersion && version_compare($currentVersion, $latestVersion, '<');

        return response()->json([
            'has_update' => $hasUpdate,
            'current_version' => $currentVersion,
            'latest_version' => $latestVersion,
            'product' => new \App\Http\Resources\Api\V1\ProductResource($product),
            'update_url' => $hasUpdate ? config('app.url') . '/downloads/' . $product->slug : null,
        ]);
    }
}


