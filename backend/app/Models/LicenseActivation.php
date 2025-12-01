<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LicenseActivation extends Model
{
    use HasFactory;

    protected $fillable = [
        'license_id',
        'activation_type',
        'activation_value',
        'activation_hash',
        'ip_address',
        'user_agent',
        'status',
        'activated_at',
        'last_check',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'last_check' => 'datetime',
    ];

    public function license()
    {
        return $this->belongsTo(License::class);
    }
}


