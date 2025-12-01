<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'reply_id',
        'filename',
        'file_path',
        'file_size',
        'mime_type',
        'uploaded_by',
    ];

    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    public function reply()
    {
        return $this->belongsTo(TicketReply::class, 'reply_id');
    }
}


