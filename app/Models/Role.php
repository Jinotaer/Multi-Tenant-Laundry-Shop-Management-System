<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class Role extends Model
{
    /** @use HasFactory<\Database\Factories\RoleFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('assigned_by')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Permission, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class)
            ->withTimestamps();
    }

    /**
     * @return Collection<int, array{slug: string, name: string, description: string, permissions: list<string>}>
     */
    public static function definitions(): Collection
    {
        return collect(config('tenant_roles', []));
    }

    public static function ensureDefaultsExist(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        static::definitions()->each(function (array $roleDefinition): void {
            $role = static::query()->updateOrCreate(
                ['slug' => $roleDefinition['slug']],
                [
                    'name' => $roleDefinition['name'],
                    'description' => $roleDefinition['description'],
                ],
            );

            if (! Schema::hasTable('permissions') || ! Schema::hasTable('permission_role')) {
                return;
            }

            $permissionKeys = $roleDefinition['permissions'];
            $permissionIds = $permissionKeys === ['*']
                ? Permission::query()->pluck('id')->all()
                : Permission::query()->whereIn('key', $permissionKeys)->pluck('id')->all();

            if ($permissionIds === []) {
                return;
            }

            $role->permissions()->syncWithoutDetaching($permissionIds);
        });

    }
}
