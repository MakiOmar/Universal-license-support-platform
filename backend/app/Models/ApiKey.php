<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiKey extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'customer_id',
        'product_id',
        'name',
        'key',
        'secret_hash',
        'rate_limit',
        'trial_days',
        'status',
        'last_used_at',
        'expires_at',
    ];

    protected $hidden = [
        'secret_hash',
    ];

    protected function casts(): array
    {
        return [
            'rate_limit' => 'integer',
            'trial_days' => 'integer',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function allowsTrials(): bool
    {
        return $this->trialDays() > 0 && $this->product_id !== null;
    }

    public function trialDays(): int
    {
        return max(0, (int) ($this->trial_days ?? 0));
    }

    /**
     * Lightweight payload for apps that need trial length before start-trial.
     *
     * @return array{enabled: bool, trial_days: int, product_id: int|null}
     */
    public function trialInfo(): array
    {
        return [
            'enabled' => $this->allowsTrials(),
            'trial_days' => $this->trialDays(),
            'product_id' => $this->product_id ? (int) $this->product_id : null,
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isActive(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }
}
