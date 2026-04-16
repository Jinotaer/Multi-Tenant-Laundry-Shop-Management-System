<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessage extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $fillable = [
        'support_ticket_id',
        'sender_type',
        'sender_id',
        'message',
        'attachments',
        'attachment_paths',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'attachment_paths' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function isTenantMessage(): bool
    {
        return $this->sender_type === 'tenant';
    }

    public function isAdminMessage(): bool
    {
        return $this->sender_type === 'admin';
    }
}
