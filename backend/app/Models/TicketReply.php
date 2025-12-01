<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'user_type',
        'message',
        'is_internal',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class, 'reply_id');
    }
}


