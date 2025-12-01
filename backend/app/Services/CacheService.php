<?php

namespace App\Services;

use App\Models\License;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class CacheService
{
    /**
     * Cache duration in seconds (5 minutes).
     */
    protected int $defaultTtl = 300;

    /**
     * Get cached product or fetch and cache.
     */
    public function getProduct(int $productId): ?Product
    {
        return Cache::remember(
            "product_{$productId}",
            $this->defaultTtl,
            fn () => Product::find($productId)
        );
    }

    /**
     * Get cached license validation result.
     */
    public function getLicenseValidation(string $licenseKey): ?array
    {
        return Cache::remember(
            "license_validation_{$licenseKey}",
            60, // 1 minute for validation
            function () use ($licenseKey) {
                $license = License::where('license_key', $licenseKey)->first();

                if (! $license) {
                    return ['valid' => false, 'reason' => 'not_found'];
                }

                if ($license->status !== 'active') {
                    return ['valid' => false, 'reason' => $license->status];
                }

                if ($license->expires_at && $license->expires_at->isPast()) {
                    return ['valid' => false, 'reason' => 'expired'];
                }

                return ['valid' => true];
            }
        );
    }

    /**
     * Clear license validation cache.
     */
    public function clearLicenseValidation(string $licenseKey): void
    {
        Cache::forget("license_validation_{$licenseKey}");
    }

    /**
     * Clear product cache.
     */
    public function clearProduct(int $productId): void
    {
        Cache::forget("product_{$productId}");
    }

    /**
     * Clear all caches related to a license.
     */
    public function clearLicenseCache(int $licenseId): void
    {
        $license = License::find($licenseId);
        if ($license) {
            $this->clearLicenseValidation($license->license_key);
        }
    }
}

