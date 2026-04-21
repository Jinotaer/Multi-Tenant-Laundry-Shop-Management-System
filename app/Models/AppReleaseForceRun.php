<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppReleaseForceRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_release_id',
        'admin_id',
        'status',
        'deployment_success',
        'deployment_error',
        'total_tenants',
        'successful_tenants',
        'failed_tenants',
        'failed_tenant_ids',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'deployment_success' => 'boolean',
        'failed_tenant_ids' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function release(): BelongsTo
    {
        return $this->belongsTo(AppRelease::class, 'app_release_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
