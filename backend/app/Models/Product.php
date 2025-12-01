<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'version',
        'status',
    ];

    public function licenses()
    {
        return $this->hasMany(License::class);
    }

    public function apiKeys()
    {
        return $this->hasMany(ApiKey::class);
    }

    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class);
    }
}


