<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Str;

class LicenseKeyGenerator
{
    public function generate(Product $product): string
    {
        $prefix = $this->normalizePrefix($product->key_prefix);
        $segments = [];

        for ($i = 0; $i < 4; $i++) {
            $segments[] = strtoupper(Str::random(4));
        }

        return $prefix.'-'.implode('-', $segments);
    }

    /**
     * Normalize a product key prefix for license keys (uppercase alphanumeric).
     */
    public function normalizePrefix(?string $prefix): string
    {
        $normalized = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', (string) $prefix));

        return $normalized !== '' ? $normalized : 'ULSP';
    }
}
