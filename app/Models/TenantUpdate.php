<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantUpdate extends Model
{
    public function getConnectionName()
    {
        return config('tenancy.database.central_connection');
    }

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'action_taken_at' => 'datetime',
            'is_current' => 'boolean',
        ];
    }

    public function release()
    {
        return $this->belongsTo(AppRelease::class, 'app_release_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
