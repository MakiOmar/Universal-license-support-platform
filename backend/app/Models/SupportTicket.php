<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'customer_id',
        'license_id',
        'product_id',
        'subject',
        'description',
        'priority',
        'status',
        'category',
        'assigned_to',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function license()
    {
        return $this->belongsTo(License::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function replies()
    {
        return $this->hasMany(TicketReply::class, 'ticket_id');
    }

    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class, 'ticket_id');
    }
}


