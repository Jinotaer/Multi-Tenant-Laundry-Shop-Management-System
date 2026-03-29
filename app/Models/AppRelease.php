<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppRelease extends Model
{
    public function getConnectionName()
    {
        return config('tenancy.database.central_connection');
    }

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_prerelease' => 'boolean',
            'is_required' => 'boolean',
        ];
    }
}
