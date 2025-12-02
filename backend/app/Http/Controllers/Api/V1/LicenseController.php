<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ActivateLicenseRequest;
use App\Http\Requests\Api\V1\StoreLicenseRequest;
use App\Http\Resources\Api\V1\LicenseResource;
use App\Models\License;
use App\Services\CacheService;
use App\Services\LicenseActivationService;
use App\Services\LicenseKeyGenerator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LicenseController extends Controller
{
    public function __construct(
        protected LicenseActivationService $activationService,
        protected LicenseKeyGenerator $keyGenerator,
        protected CacheService $cacheService
    ) {
    }

    public function index(Request $request)
    {
        $perPage = min($request->get('per_page', 25), 100); // Max 100 per page

        $licenses = License::with(['product:id,name,slug,type', 'customer:id,email,first_name,last_name', 'activations:id,license_id,activation_type,status'])
            ->select('id', 'license_key', 'product_id', 'customer_id', 'license_type', 'max_activations', 'status', 'expires_at', 'created_at')
            ->paginate($perPage);

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
        $purchasedAt = $data['purchased_at'] ?? now();
        $purchasedDate = \Carbon\Carbon::parse($purchasedAt);
        $data['purchased_at'] = $purchasedDate->format('Y-m-d H:i:s');

        // Calculate expiration dates from periods if provided
        if (! empty($data['expires_period_value']) && ! empty($data['expires_period_unit'])) {
            $unit = rtrim($data['expires_period_unit'], 's'); // Normalize: days -> day, months -> month, etc.
            $data['expires_at'] = $purchasedDate->copy()->add($data['expires_period_value'], $unit)->format('Y-m-d H:i:s');
        }
        unset($data['expires_period_value'], $data['expires_period_unit']);

        if (! empty($data['support_expires_period_value']) && ! empty($data['support_expires_period_unit'])) {
            $unit = rtrim($data['support_expires_period_unit'], 's');
            $data['support_expires_at'] = $purchasedDate->copy()->add($data['support_expires_period_value'], $unit)->format('Y-m-d H:i:s');
        }
        unset($data['support_expires_period_value'], $data['support_expires_period_unit']);

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
            // Period-based expiration (alternative to direct date)
            'expires_period_value' => ['nullable', 'integer', 'min:1'],
            'expires_period_unit' => ['nullable', 'string', Rule::in(['day', 'days', 'month', 'months', 'year', 'years'])],
            'support_expires_period_value' => ['nullable', 'integer', 'min:1'],
            'support_expires_period_unit' => ['nullable', 'string', Rule::in(['day', 'days', 'month', 'months', 'year', 'years'])],
            // Direct date inputs (for editing existing licenses)
            'expires_at' => ['nullable', 'date', 'after_or_equal:purchased_at'],
            'support_expires_at' => ['nullable', 'date', 'after_or_equal:purchased_at'],
        ]);

        // Calculate expiration dates from periods if provided
        $purchasedAt = $data['purchased_at'] ?? $license->purchased_at ?? now();
        $purchasedDate = \Carbon\Carbon::parse($purchasedAt);

        if (! empty($data['expires_period_value']) && ! empty($data['expires_period_unit'])) {
            $unit = rtrim($data['expires_period_unit'], 's');
            $data['expires_at'] = $purchasedDate->copy()->add($data['expires_period_value'], $unit)->format('Y-m-d H:i:s');
        }
        unset($data['expires_period_value'], $data['expires_period_unit']);

        if (! empty($data['support_expires_period_value']) && ! empty($data['support_expires_period_unit'])) {
            $unit = rtrim($data['support_expires_period_unit'], 's');
            $data['support_expires_at'] = $purchasedDate->copy()->add($data['support_expires_period_value'], $unit)->format('Y-m-d H:i:s');
        }
        unset($data['support_expires_period_value'], $data['support_expires_period_unit']);

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
            'license_key' => ['required', 'string', 'max:255'],
        ]);

        // Use cache for validation (frequently called endpoint)
        $result = $this->cacheService->getLicenseValidation($data['license_key']);

        if (! $result['valid']) {
            $statusCode = $result['reason'] === 'not_found' ? 404 : 200;
            return response()->json($result, $statusCode);
        }

        return response()->json($result, 200);
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


