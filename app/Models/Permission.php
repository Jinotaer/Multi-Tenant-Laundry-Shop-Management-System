<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

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
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)
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

        static::migrateLegacyPermissions();
        Role::ensureDefaultsExist();
    }

    private static function migrateLegacyPermissions(): void
    {
        $legacyMappings = [
            'permissions.manage' => ['staff.assign_permissions'],
            'services.manage' => ['services.view', 'services.create', 'services.update', 'services.delete'],
            'expenses.manage' => ['expenses.view', 'expenses.create', 'expenses.update', 'expenses.delete'],
            'inventory.manage' => ['inventory.view', 'inventory.create', 'inventory.update', 'inventory.delete', 'inventory.adjust'],
        ];

        foreach ($legacyMappings as $legacyKey => $replacementKeys) {
            $legacyPermission = static::query()->where('key', $legacyKey)->first();

            if ($legacyPermission === null) {
                continue;
            }

            $replacementIds = static::query()
                ->whereIn('key', $replacementKeys)
                ->pluck('id')
                ->all();

            if (Schema::hasTable('user_permissions')) {
                $legacyPermission->users()->get()->each(function (User $user) use ($replacementIds): void {
                    $syncPayload = [];

                    foreach ($replacementIds as $permissionId) {
                        $syncPayload[$permissionId] = ['granted_by' => $user->pivot->granted_by];
                    }

                    if ($syncPayload !== []) {
                        $user->permissions()->syncWithoutDetaching($syncPayload);
                    }
                });

                $legacyPermission->users()->detach();
            }

            if (Schema::hasTable('permission_role')) {
                $legacyPermission->roles()->get()->each(function (Role $role) use ($replacementIds): void {
                    if ($replacementIds !== []) {
                        $role->permissions()->syncWithoutDetaching($replacementIds);
                    }
                });

                $legacyPermission->roles()->detach();
            }

            $legacyPermission->delete();
        }
    }
}
