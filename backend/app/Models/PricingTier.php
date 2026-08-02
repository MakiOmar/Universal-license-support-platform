<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PricingTier extends Model
{
    use HasFactory;

    public const BILLING_MONTHLY = 'monthly';

    public const BILLING_YEARLY = 'yearly';

    public const BILLING_ONE_TIME = 'one_time';

    public const BILLING_LIFETIME = 'lifetime';

    protected $fillable = [
        'product_id',
        'name',
        'price',
        'currency',
        'max_activations',
        'billing_cycle',
        'stripe_price_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function billingCycleOptions(): array
    {
        return [
            self::BILLING_MONTHLY => 'Monthly',
            self::BILLING_YEARLY => 'Yearly',
            self::BILLING_ONE_TIME => 'One-time payment',
            self::BILLING_LIFETIME => 'Lifetime',
        ];
    }

    public function isRecurring(): bool
    {
        return in_array($this->billing_cycle, [
            self::BILLING_MONTHLY,
            self::BILLING_YEARLY,
        ], true);
    }

    public function isOneTimePayment(): bool
    {
        return in_array($this->billing_cycle, [
            self::BILLING_ONE_TIME,
            self::BILLING_LIFETIME,
        ], true);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }
}
