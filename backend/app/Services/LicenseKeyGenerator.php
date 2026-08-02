<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Str;

class LicenseKeyGenerator
{
    public function generate(Product $product): string
    {
        $prefix = strtoupper($product->key_prefix ?: 'ULSP');
        $segments = [];

        for ($i = 0; $i < 4; $i++) {
            $segments[] = strtoupper(Str::random(4));
        }

        return $prefix.'-'.implode('-', $segments);
    }
}
