<?php

namespace App\Services;

use App\Models\ApiKey;
use App\Models\Customer;
use App\Models\License;
use App\Models\LicenseActivation;
use App\Models\PricingTier;
use App\Models\Product;
use App\Notifications\LicenseIssuedNotification;
use App\Notifications\LicenseSuspendedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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

            $license = License::create([
                'license_key' => $licenseKey,
                'product_id' => $product->id,
                'customer_id' => $customer->id,
                'pricing_tier_id' => $tier?->id,
                'max_activations' => $maxActivations,
                'status' => $options['status'] ?? License::STATUS_ACTIVE,
                'is_trial' => (bool) ($options['is_trial'] ?? false),
                'purchased_at' => $options['purchased_at'] ?? now(),
                'expires_at' => $expiresAt,
                'support_expires_at' => array_key_exists('support_expires_at', $options)
                    ? $options['support_expires_at']
                    : $expiresAt,
            ]);

            $license->load(['product', 'customer']);

            if (($options['notify'] ?? true) === true) {
                $customer->notify(new LicenseIssuedNotification($license));
            }

            return $license;
        });
    }

    /**
     * Issue and activate a guest device trial for the API key's product.
     *
     * @param  array{device_name?: string|null, platform?: string|null, app_version?: string|null}  $deviceMeta
     * @return array{license: License, activation: LicenseActivation}
     */
    public function startTrial(
        ApiKey $apiKey,
        string $activationType,
        string $activationValue,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        array $deviceMeta = [],
    ): array {
        if ($apiKey->trialDays() < 1) {
            throw ValidationException::withMessages([
                'trial' => [__('Trials are not enabled for this API key.')],
            ]);
        }

        if (! $apiKey->product_id) {
            throw ValidationException::withMessages([
                'trial' => [__('This API key must be scoped to a product to issue trials.')],
            ]);
        }

        $product = Product::query()->findOrFail($apiKey->product_id);
        $hash = LicenseActivation::hashActivation($activationType, $activationValue);

        $alreadyUsed = LicenseActivation::query()
            ->where('activation_hash', $hash)
            ->whereHas('license', function ($query) use ($product): void {
                $query->where('product_id', $product->id)
                    ->where('is_trial', true);
            })
            ->exists();

        if ($alreadyUsed) {
            throw ValidationException::withMessages([
                'trial' => [__('A trial has already been used on this device for this product.')],
            ]);
        }

        return DB::transaction(function () use (
            $apiKey,
            $product,
            $activationType,
            $activationValue,
            $ipAddress,
            $userAgent,
            $deviceMeta,
        ): array {
            $license = $this->issue(
                $this->trialCustomer(),
                $product,
                null,
                [
                    'max_activations' => 1,
                    'expires_at' => now()->addDays($apiKey->trialDays()),
                    'is_trial' => true,
                    'notify' => false,
                    'status' => License::STATUS_ACTIVE,
                ],
            );

            $activation = $this->activate(
                $license->license_key,
                $activationType,
                $activationValue,
                $ipAddress,
                $userAgent,
                $product->id,
                false,
                $deviceMeta,
            );

            return [
                'license' => $license->fresh(['product']),
                'activation' => $activation,
            ];
        });
    }

    public function trialCustomer(): Customer
    {
        $email = (string) config('ulsp.trial_customer_email', 'trials@ulsp.local');

        return Customer::query()->firstOrCreate(
            ['email' => $email],
            [
                'password' => Hash::make(Str::random(64)),
                'first_name' => 'Trial',
                'last_name' => 'Guest',
                'company' => 'ULSP System',
                'status' => 'active',
                'email_verified_at' => now(),
            ],
        );
    }

    public function validate(
        string $licenseKey,
        ?string $activationType = null,
        ?string $activationValue = null,
        ?int $productId = null,
        array $deviceMeta = [],
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
            $activation = $license->activations()
                ->where('activation_hash', $hash)
                ->where('status', LicenseActivation::STATUS_ACTIVE)
                ->first();

            $activationValid = $activation !== null;

            // Refresh last check and device meta when the app sends them on validate.
            if ($activation) {
                $updates = ['last_check_at' => now()];
                foreach (['device_name', 'platform', 'app_version'] as $field) {
                    if (! empty($deviceMeta[$field])) {
                        $updates[$field] = $deviceMeta[$field];
                    }
                }
                $activation->update($updates);
            }
        }

        return [
            'valid' => true,
            'license' => $license->fresh(['product', 'activations']),
            'activation_valid' => $activationValid,
            'activations_used' => $license->activeActivationsCount(),
            'max_activations' => $license->max_activations,
            'expires_at' => $license->expires_at,
        ];
    }

    /**
     * @param  array{device_name?: string|null, platform?: string|null, app_version?: string|null}  $deviceMeta
     */
    public function activate(
        string $licenseKey,
        string $activationType,
        string $activationValue,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?int $productId = null,
        bool $replaceOldest = false,
        array $deviceMeta = [],
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
                $existing->update(array_filter([
                    'last_check_at' => now(),
                    'device_name' => $deviceMeta['device_name'] ?? $existing->device_name,
                    'platform' => $deviceMeta['platform'] ?? $existing->platform,
                    'app_version' => $deviceMeta['app_version'] ?? $existing->app_version,
                ], fn ($value) => $value !== null));

                return $existing->fresh();
            }

            $existing->update([
                'status' => LicenseActivation::STATUS_ACTIVE,
                'activated_at' => now(),
                'last_check_at' => now(),
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'device_name' => $deviceMeta['device_name'] ?? $existing->device_name,
                'platform' => $deviceMeta['platform'] ?? $existing->platform,
                'app_version' => $deviceMeta['app_version'] ?? $existing->app_version,
            ]);

            return $existing->fresh();
        }

        if ($license->activeActivationsCount() >= $license->max_activations) {
            if (! $replaceOldest) {
                throw ValidationException::withMessages([
                    'activation' => [__('Maximum activations reached for this license.')],
                ]);
            }

            $oldest = $license->activations()
                ->where('status', LicenseActivation::STATUS_ACTIVE)
                ->orderByRaw('last_check_at IS NULL')
                ->orderBy('last_check_at')
                ->orderBy('activated_at')
                ->first();

            if ($oldest) {
                $oldest->update(['status' => LicenseActivation::STATUS_DEACTIVATED]);
            }
        }

        return LicenseActivation::create([
            'license_id' => $license->id,
            'activation_type' => $activationType,
            'activation_value' => $activationValue,
            'activation_hash' => $hash,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'device_name' => $deviceMeta['device_name'] ?? null,
            'platform' => $deviceMeta['platform'] ?? null,
            'app_version' => $deviceMeta['app_version'] ?? null,
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

    public function suspend(License $license, bool $notify = true): License
    {
        $license->update(['status' => License::STATUS_SUSPENDED]);
        $license = $license->fresh(['customer', 'product']);

        if ($notify && $license?->customer) {
            $license->customer->notify(new LicenseSuspendedNotification($license));
        }

        return $license;
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
