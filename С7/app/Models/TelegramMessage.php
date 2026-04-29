<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramMessage extends Model
{
    protected $fillable = [
        'message_id',
        'chat_id',
        'user_id',
        'username',
        'first_name',
        'text',
        'message_type',
        'is_read',
        'is_replied',
        'reply_text',
        'assigned_to',
        'received_at'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'is_replied' => 'boolean',
        'received_at' => 'datetime'
    ];

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}