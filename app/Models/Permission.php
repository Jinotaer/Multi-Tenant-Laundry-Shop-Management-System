<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class Permission extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'label',
        'module',
    ];

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_permissions')
            ->withPivot('granted_by')
            ->withTimestamps();
    }

    /**
     * @return Collection<int, array{key: string, label: string, module: string}>
     */
    public static function definitions(): Collection
    {
        return collect(config('tenant_permissions', []));
    }

    /**
     * Ensure all configured permission records exist in the tenant DB.
     */
    public static function ensureDefaultsExist(): void
    {
        static::definitions()->each(function (array $permission): void {
            static::query()->updateOrCreate(
                ['key' => $permission['key']],
                [
                    'label' => $permission['label'],
                    'module' => $permission['module'],
                ],
            );
        });
    }
}
