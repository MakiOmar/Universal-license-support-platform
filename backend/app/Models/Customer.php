<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'first_name',
        'last_name',
        'company',
        'phone',
        'password_hash',
        'status',
    ];

    public function licenses()
    {
        return $this->hasMany(License::class);
    }

    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function apiKeys()
    {
        return $this->hasMany(ApiKey::class);
    }

    public function activities()
    {
        return $this->hasMany(CustomerActivity::class)->orderByDesc('created_at');
    }
}


