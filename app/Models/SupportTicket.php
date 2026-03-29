<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicket extends Model
{
    use HasFactory;

    /**
     * Support tickets live in the central database.
     *
     * @var string
     */
    protected $connection = 'mysql';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'submitted_by_name',
        'submitted_by_email',
        'subject',
        'message',
        'priority',
        'status',
        'admin_notes',
        'resolved_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * Get the owning tenant.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Determine whether the ticket has been resolved.
     */
    public function isResolved(): bool
    {
        return $this->resolved_at !== null || $this->status === 'resolved';
    }
}
