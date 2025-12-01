<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Str;

class LicenseKeyGenerator
{
    /**
     * Generate a license key based on product type and format.
     *
     * @param  Product  $product
     * @return string
     */
    public function generate(Product $product): string
    {
        $slug = strtoupper(Str::slug($product->slug, ''));
        $prefix = substr($slug, 0, 8);

        // Generate random segments
        $segments = [];
        for ($i = 0; $i < 4; $i++) {
            $segments[] = strtoupper(Str::random(4));
        }

        // Format: PRODUCT-XXXX-XXXX-XXXX-XXXX
        return sprintf('%s-%s', $prefix, implode('-', $segments));
    }

    /**
     * Generate license key for specific product type.
     *
     * @param  Product  $product
     * @param  string|null  $activationValue
     * @return string
     */
    public function generateForType(Product $product, ?string $activationValue = null): string
    {
        $slug = strtoupper(Str::slug($product->slug, ''));
        $prefix = substr($slug, 0, 8);

        switch ($product->type) {
            case 'wordpress_plugin':
            case 'web_app':
                // Format: PRODUCT-XXXX-XXXX-XXXX-XXXX
                return $this->generate($product);

            case 'desktop_app':
                // Format: PRODUCT-{MACHINE_ID}-XXXX
                $machineId = $activationValue ? substr(hash('sha256', $activationValue), 0, 8) : Str::random(8);
                return sprintf('%s-%s-%s', $prefix, $machineId, strtoupper(Str::random(4)));

            case 'mobile_app':
                // Format: PRODUCT-{DEVICE_ID}-XXXX
                $deviceId = $activationValue ? substr(hash('sha256', $activationValue), 0, 8) : Str::random(8);
                return sprintf('%s-%s-%s', $prefix, $deviceId, strtoupper(Str::random(4)));

            case 'api_service':
                // Format: API-KEY-{RANDOM}-{RANDOM}
                return sprintf('API-KEY-%s-%s', strtoupper(Str::random(6)), strtoupper(Str::random(6)));

            default:
                return $this->generate($product);
        }
    }

    /**
     * Validate license key format.
     *
     * @param  string  $licenseKey
     * @return bool
     */
    public function isValidFormat(string $licenseKey): bool
    {
        // Basic validation: should contain alphanumeric and hyphens
        return (bool) preg_match('/^[A-Z0-9-]{8,}$/i', $licenseKey);
    }
}

