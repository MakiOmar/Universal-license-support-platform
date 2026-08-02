<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\License;
use App\Models\LicenseActivation;
use App\Models\PricingTier;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LicenseService
{
    public function __construct(
        protected LicenseKeyGenerator $keyGenerator,
    ) {}

    public function issue(
        Customer $customer,
        Product $product,
        ?PricingTier $tier = null,
        array $options = [],
    ): License {
        return DB::transaction(function () use ($customer, $product, $tier, $options) {
            do {
                $licenseKey = $this->keyGenerator->generate($product);
            } while (License::where('license_key', $licenseKey)->exists());

            $maxActivations = $options['max_activations']
                ?? $tier?->max_activations
                ?? 1;

            $billingCycle = $tier?->billing_cycle ?? PricingTier::BILLING_YEARLY;
            $expiresAt = array_key_exists('expires_at', $options)
                ? $options['expires_at']
                : $this->expiresAtForBillingCycle($billingCycle);

            return License::create([
                'license_key' => $licenseKey,
                'product_id' => $product->id,
                'customer_id' => $customer->id,
                'pricing_tier_id' => $tier?->id,
                'max_activations' => $maxActivations,
                'status' => $options['status'] ?? License::STATUS_ACTIVE,
                'purchased_at' => $options['purchased_at'] ?? now(),
                'expires_at' => $expiresAt,
                'support_expires_at' => array_key_exists('support_expires_at', $options)
                    ? $options['support_expires_at']
                    : $expiresAt,
            ]);
        });
    }

    public function validate(
        string $licenseKey,
        ?string $activationType = null,
        ?string $activationValue = null,
        ?int $productId = null,
    ): array {
        $license = License::with(['product', 'activations'])
            ->where('license_key', $licenseKey)
            ->first();

        if (! $license) {
            return ['valid' => false, 'reason' => 'license_not_found'];
        }

        // When the API key is scoped to a product, reject licenses for other products.
        if ($productId !== null && (int) $license->product_id !== (int) $productId) {
            return ['valid' => false, 'reason' => 'license_product_mismatch'];
        }

        if ($license->status === License::STATUS_SUSPENDED) {
            return ['valid' => false, 'reason' => 'license_suspended', 'license' => $license];
        }

        if ($license->status === License::STATUS_CANCELLED) {
            return ['valid' => false, 'reason' => 'license_cancelled', 'license' => $license];
        }

        if ($license->isExpired() || $license->status === License::STATUS_EXPIRED) {
            return ['valid' => false, 'reason' => 'license_expired', 'license' => $license];
        }

        $activationValid = true;
        if ($activationType && $activationValue) {
            $hash = LicenseActivation::hashActivation($activationType, $activationValue);
            $activationValid = $license->activations()
                ->where('activation_hash', $hash)
                ->where('status', LicenseActivation::STATUS_ACTIVE)
                ->exists();
        }

        return [
            'valid' => true,
            'license' => $license,
            'activation_valid' => $activationValid,
            'activations_used' => $license->activeActivationsCount(),
            'max_activations' => $license->max_activations,
            'expires_at' => $license->expires_at,
        ];
    }

    public function activate(
        string $licenseKey,
        string $activationType,
        string $activationValue,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?int $productId = null,
    ): LicenseActivation {
        $validation = $this->validate($licenseKey, null, null, $productId);

        if (! ($validation['valid'] ?? false)) {
            $reason = $validation['reason'] ?? 'invalid';

            $message = match ($reason) {
                'license_product_mismatch' => __('This license does not belong to this product.'),
                default => __('Invalid or inactive license.'),
            };

            throw ValidationException::withMessages([
                'license_key' => [$message],
            ]);
        }

        /** @var License $license */
        $license = $validation['license'];
        $hash = LicenseActivation::hashActivation($activationType, $activationValue);

        $existing = $license->activations()->where('activation_hash', $hash)->first();

        if ($existing) {
            if ($existing->status === LicenseActivation::STATUS_ACTIVE) {
                $existing->update(['last_check_at' => now()]);

                return $existing->fresh();
            }

            $existing->update([
                'status' => LicenseActivation::STATUS_ACTIVE,
                'activated_at' => now(),
                'last_check_at' => now(),
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);

            return $existing->fresh();
        }

        if ($license->activeActivationsCount() >= $license->max_activations) {
            throw ValidationException::withMessages([
                'activation' => [__('Maximum activations reached for this license.')],
            ]);
        }

        return LicenseActivation::create([
            'license_id' => $license->id,
            'activation_type' => $activationType,
            'activation_value' => $activationValue,
            'activation_hash' => $hash,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'status' => LicenseActivation::STATUS_ACTIVE,
            'activated_at' => now(),
            'last_check_at' => now(),
        ]);
    }

    public function deactivate(string $licenseKey, string $activationHash): bool
    {
        $license = License::where('license_key', $licenseKey)->first();

        if (! $license) {
            return false;
        }

        $activation = $license->activations()
            ->where('activation_hash', $activationHash)
            ->where('status', LicenseActivation::STATUS_ACTIVE)
            ->first();

        if (! $activation) {
            return false;
        }

        $activation->update(['status' => LicenseActivation::STATUS_DEACTIVATED]);

        return true;
    }

    public function transfer(License $license, Customer $newCustomer): License
    {
        $license->update(['customer_id' => $newCustomer->id]);

        return $license->fresh();
    }

    public function renew(License $license, ?PricingTier $tier = null): License
    {
        $billingCycle = $tier?->billing_cycle
            ?? $license->pricingTier?->billing_cycle
            ?? PricingTier::BILLING_YEARLY;

        // One-time / lifetime licenses stay non-expiring.
        if (in_array($billingCycle, [PricingTier::BILLING_ONE_TIME, PricingTier::BILLING_LIFETIME], true)) {
            $license->update([
                'pricing_tier_id' => $tier?->id ?? $license->pricing_tier_id,
                'status' => License::STATUS_ACTIVE,
                'expires_at' => null,
                'support_expires_at' => null,
            ]);

            return $license->fresh();
        }

        $baseDate = $license->expires_at && $license->expires_at->isFuture()
            ? $license->expires_at
            : now();

        $license->update([
            'pricing_tier_id' => $tier?->id ?? $license->pricing_tier_id,
            'status' => License::STATUS_ACTIVE,
            'expires_at' => $baseDate->copy()->add($this->billingCycleInterval($billingCycle)),
            'support_expires_at' => $baseDate->copy()->add($this->billingCycleInterval($billingCycle)),
        ]);

        return $license->fresh();
    }

    public function suspend(License $license): License
    {
        $license->update(['status' => License::STATUS_SUSPENDED]);

        return $license->fresh();
    }

    protected function expiresAtForBillingCycle(string $cycle): ?\DateTimeInterface
    {
        if (in_array($cycle, [PricingTier::BILLING_ONE_TIME, PricingTier::BILLING_LIFETIME], true)) {
            return null;
        }

        return now()->add($this->billingCycleInterval($cycle));
    }

    protected function billingCycleInterval(string $cycle): \DateInterval
    {
        return match ($cycle) {
            PricingTier::BILLING_MONTHLY => new \DateInterval('P1M'),
            PricingTier::BILLING_YEARLY => new \DateInterval('P1Y'),
            default => new \DateInterval('P1Y'),
        };
    }
}
