<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    use HasFactory;

    protected $fillable = [
        'license_key',
        'product_id',
        'customer_id',
        'license_type',
        'max_activations',
        'status',
        'purchased_at',
        'expires_at',
        'support_expires_at',
    ];

    protected $casts = [
        'purchased_at' => 'datetime',
        'expires_at' => 'datetime',
        'support_expires_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function activations()
    {
        return $this->hasMany(LicenseActivation::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}


