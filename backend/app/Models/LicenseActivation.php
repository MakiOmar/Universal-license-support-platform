<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseActivation extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_DEACTIVATED = 'deactivated';

    protected $fillable = [
        'license_id',
        'activation_type',
        'activation_value',
        'activation_hash',
        'ip_address',
        'user_agent',
        'device_name',
        'platform',
        'app_version',
        'status',
        'activated_at',
        'last_check_at',
    ];

    protected function casts(): array
    {
        return [
            'activated_at' => 'datetime',
            'last_check_at' => 'datetime',
        ];
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public static function hashActivation(string $activationType, string $activationValue): string
    {
        return hash('sha256', strtolower($activationType).'|'.strtolower(trim($activationValue)));
    }
}
