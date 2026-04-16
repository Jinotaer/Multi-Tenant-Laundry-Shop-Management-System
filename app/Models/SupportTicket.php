<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'category',
        'status',
        'assigned_to',
        'admin_notes',
        'resolved_at',
        'first_response_at',
        'sla_due_at',
        'sla_breached',
        'unread_tenant_count',
        'unread_admin_count',
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
            'first_response_at' => 'datetime',
            'sla_due_at' => 'datetime',
            'sla_breached' => 'boolean',
            'unread_tenant_count' => 'integer',
            'unread_admin_count' => 'integer',
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
     * Get all messages for this ticket.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class);
    }

    /**
     * Determine whether the ticket has been resolved.
     */
    public function isResolved(): bool
    {
        return $this->resolved_at !== null || $this->status === 'resolved';
    }

    public function calculateSLA(): void
    {
        if ($this->sla_due_at) {
            return;
        }

        $hours = match($this->priority) {
            'urgent' => 1,
            'priority' => 4,
            default => 24,
        };

        $this->update(['sla_due_at' => now()->addHours($hours)]);
    }

    public function markFirstResponse(): void
    {
        if (!$this->first_response_at) {
            $this->update(['first_response_at' => now()]);
        }
    }

    public function checkSLABreach(): void
    {
        if ($this->sla_due_at && now()->isAfter($this->sla_due_at) && !$this->first_response_at) {
            $this->update(['sla_breached' => true]);
        }
    }

    public function incrementUnreadTenant(): void
    {
        $this->increment('unread_tenant_count');
    }

    public function incrementUnreadAdmin(): void
    {
        $this->increment('unread_admin_count');
    }

    public function markReadByTenant(): void
    {
        $this->update(['unread_tenant_count' => 0]);
    }

    public function markReadByAdmin(): void
    {
        $this->update(['unread_admin_count' => 0]);
    }
}
