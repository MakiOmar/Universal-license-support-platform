<?php

namespace App\Services;

use App\Models\License;
use App\Models\LicenseActivation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LicenseActivationService
{
    /**
     * Activate a license for a given activation value.
     *
     * @param  License  $license
     * @param  string  $activationType
     * @param  string  $activationValue
     * @param  Request  $request
     * @return array
     */
    public function activate(License $license, string $activationType, string $activationValue, Request $request): array
    {
        // Validate license status
        if ($license->status !== 'active') {
            return [
                'success' => false,
                'message' => 'License is not active.',
                'reason' => $license->status,
            ];
        }

        // Check expiration
        if ($license->expires_at && $license->expires_at->isPast()) {
            return [
                'success' => false,
                'message' => 'License has expired.',
                'reason' => 'expired',
            ];
        }

        // Generate activation hash
        $hash = hash('sha256', $activationType . '|' . $activationValue);

        // Check if already activated
        $existing = $license->activations()
            ->where('activation_hash', $hash)
            ->where('status', 'active')
            ->first();

        if ($existing) {
            return [
                'success' => true,
                'message' => 'Already activated.',
                'activation_id' => $existing->id,
                'already_exists' => true,
            ];
        }

        // Check max activations
        $currentCount = $license->activations()
            ->where('status', 'active')
            ->count();

        if ($currentCount >= $license->max_activations) {
            return [
                'success' => false,
                'message' => 'Maximum number of activations reached.',
                'current' => $currentCount,
                'max' => $license->max_activations,
            ];
        }

        // Create activation
        $activation = LicenseActivation::create([
            'license_id' => $license->id,
            'activation_type' => $activationType,
            'activation_value' => $activationValue,
            'activation_hash' => $hash,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->header('User-Agent', ''),
            'status' => 'active',
            'activated_at' => now(),
            'last_check' => now(),
        ]);

        Log::info('License activated', [
            'license_id' => $license->id,
            'activation_id' => $activation->id,
            'activation_type' => $activationType,
        ]);

        return [
            'success' => true,
            'message' => 'License activated successfully.',
            'activation_id' => $activation->id,
            'current_activations' => $currentCount + 1,
            'max_activations' => $license->max_activations,
        ];
    }

    /**
     * Deactivate a license activation.
     *
     * @param  License  $license
     * @param  string  $activationType
     * @param  string  $activationValue
     * @return array
     */
    public function deactivate(License $license, string $activationType, string $activationValue): array
    {
        $hash = hash('sha256', $activationType . '|' . $activationValue);

        $activation = $license->activations()
            ->where('activation_hash', $hash)
            ->where('status', 'active')
            ->first();

        if (! $activation) {
            return [
                'success' => false,
                'message' => 'Activation not found.',
            ];
        }

        $activation->status = 'inactive';
        $activation->save();

        Log::info('License deactivated', [
            'license_id' => $license->id,
            'activation_id' => $activation->id,
        ]);

        return [
            'success' => true,
            'message' => 'License deactivated successfully.',
        ];
    }

    /**
     * Get all active activations for a license.
     *
     * @param  License  $license
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveActivations(License $license)
    {
        return $license->activations()
            ->where('status', 'active')
            ->get();
    }
}

