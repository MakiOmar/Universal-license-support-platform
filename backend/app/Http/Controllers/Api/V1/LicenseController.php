<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\LicenseActivation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LicenseController extends Controller
{
    public function index()
    {
        $licenses = License::with(['product', 'customer'])->paginate(25);

        return response()->json($licenses);
    }

    public function show(License $license)
    {
        $license->load(['product', 'customer', 'activations']);

        return response()->json($license);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'license_key' => ['required', 'string', 'max:255', 'unique:licenses,license_key'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'license_type' => ['required', 'string', 'max:50'],
            'max_activations' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', 'max:20'],
            'purchased_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'support_expires_at' => ['nullable', 'date'],
        ]);

        $license = License::create($data);

        return response()->json($license, 201);
    }

    public function update(Request $request, License $license)
    {
        $data = $request->validate([
            'license_key' => ['sometimes', 'string', 'max:255', 'unique:licenses,license_key,' . $license->id],
            'product_id' => ['sometimes', 'integer', 'exists:products,id'],
            'customer_id' => ['sometimes', 'integer', 'exists:customers,id'],
            'license_type' => ['sometimes', 'string', 'max:50'],
            'max_activations' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', 'max:20'],
            'purchased_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'support_expires_at' => ['nullable', 'date'],
        ]);

        $license->update($data);

        return response()->json($license);
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

    public function activate(Request $request)
    {
        $data = $request->validate([
            'license_key' => ['required', 'string'],
            'activation_type' => ['required', 'string', 'max:50'],
            'activation_value' => ['required', 'string', 'max:255'],
        ]);

        $license = License::where('license_key', $data['license_key'])->firstOrFail();

        if ($license->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'License is not active.',
            ], 400);
        }

        $hash = hash('sha256', $data['activation_type'] . '|' . $data['activation_value']);

        if ($license->activations()->where('activation_hash', $hash)->exists()) {
            return response()->json([
                'success' => true,
                'message' => 'Already activated.',
            ]);
        }

        $currentCount = $license->activations()->count();
        if ($currentCount >= $license->max_activations) {
            return response()->json([
                'success' => false,
                'message' => 'Max activations reached.',
            ], 400);
        }

        $activation = LicenseActivation::create([
            'license_id' => $license->id,
            'activation_type' => $data['activation_type'],
            'activation_value' => $data['activation_value'],
            'activation_hash' => $hash,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->header('User-Agent', ''),
            'status' => 'active',
            'activated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'activation_id' => $activation->id,
        ], 201);
    }

    public function deactivate(Request $request)
    {
        $data = $request->validate([
            'license_key' => ['required', 'string'],
            'activation_type' => ['required', 'string', 'max:50'],
            'activation_value' => ['required', 'string', 'max:255'],
        ]);

        $license = License::where('license_key', $data['license_key'])->firstOrFail();

        $hash = hash('sha256', $data['activation_type'] . '|' . $data['activation_value']);

        $activation = $license->activations()
            ->where('activation_hash', $hash)
            ->first();

        if (! $activation) {
            return response()->json([
                'success' => false,
                'message' => 'Activation not found.',
            ], 404);
        }

        $activation->status = 'inactive';
        $activation->save();

        return response()->json([
            'success' => true,
        ]);
    }

    public function transfer(Request $request, License $license)
    {
        $data = $request->validate([
            'new_customer_id' => ['required', 'integer', 'exists:customers,id'],
        ]);

        $license->customer_id = $data['new_customer_id'];
        $license->save();

        return response()->json([
            'success' => true,
            'license' => $license,
        ]);
    }
}


