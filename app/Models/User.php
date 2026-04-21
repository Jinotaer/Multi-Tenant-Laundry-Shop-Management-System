<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::saved(function (self $user): void {
            $user->syncLegacyRoleRecord();
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'layout_preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'layout_preferences' => 'array',
            'password' => 'hashed',
        ];
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)
            ->withPivot('assigned_by')
            ->withTimestamps();
    }

    public function isOwner(): bool
    {
        return $this->hasRole('owner');
    }

    public function isStaff(): bool
    {
        return $this->hasRole('staff');
    }

    public function isCustomer(): bool
    {
        return $this->hasRole('customer');
    }

    /**
     * @param  string|list<string>  $roles
     */
    public function hasRole(string|array $roles): bool
    {
        $roleSlugs = is_array($roles) ? $roles : [$roles];

        if (in_array($this->role, $roleSlugs, true)) {
            return true;
        }

        if (! $this->exists || ! Schema::hasTable('roles') || ! Schema::hasTable('role_user')) {
            return false;
        }

        if ($this->relationLoaded('roles')) {
            return $this->roles->pluck('slug')->intersect($roleSlugs)->isNotEmpty();
        }

        return $this->roles()->whereIn('slug', $roleSlugs)->exists();
    }

    /**
     * @return BelongsToMany<Permission, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
            ->withPivot('granted_by')
            ->withTimestamps();
    }

    public function hasPermission(string $permissionKey): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        $hasDirectPermissionTables = Schema::hasTable('permissions')
            && Schema::hasTable('user_permissions');

        if ($hasDirectPermissionTables) {
            if ($this->relationLoaded('permissions') && $this->permissions->contains('key', $permissionKey)) {
                return true;
            }

            if ($this->permissions()->where('key', $permissionKey)->exists()) {
                return true;
            }
        }

        if (! Schema::hasTable('roles') || ! Schema::hasTable('role_user') || ! Schema::hasTable('permission_role')) {
            return false;
        }

        if ($this->relationLoaded('roles')) {
            return $this->roles
                ->loadMissing('permissions')
                ->contains(fn (Role $role): bool => $role->permissions->contains('key', $permissionKey));
        }

        return $this->roles()
            ->whereHas('permissions', fn ($query) => $query->where('key', $permissionKey))
            ->exists();
    }

    /**
     * @param  list<string>  $permissionKeys
     */
    public function hasAnyPermission(array $permissionKeys): bool
    {
        foreach ($permissionKeys as $permissionKey) {
            if ($this->hasPermission($permissionKey)) {
                return true;
            }
        }

        return false;
    }

    public function canGrantPermission(string $permissionKey): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        if (! $this->hasPermission('staff.assign_permissions')) {
            return false;
        }

        if (in_array($permissionKey, ['staff.assign_roles', 'staff.assign_permissions'], true)) {
            return false;
        }

        return $this->hasPermission($permissionKey);
    }

    public function canAssignRole(Role $role): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        if (! $this->hasPermission('staff.assign_roles')) {
            return false;
        }

        if (in_array($role->slug, ['owner', 'staff', 'customer'], true)) {
            return false;
        }

        return $this->canManageRolePermissions($role);
    }

    public function canManageRolePermissions(Role $role): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        $role->loadMissing('permissions');

        return $role->permissions->every(
            fn (Permission $permission): bool => $this->canGrantPermission($permission->key)
        );
    }

    /**
     * @param  list<string>  $roleSlugs
     */
    public function syncRolesBySlug(array $roleSlugs, ?self $assignedBy = null): void
    {
        Role::ensureDefaultsExist();

        if (empty($roleSlugs)) {
            $this->roles()->detach();
            return;
        }

        $roleIds = Role::query()
            ->whereIn('slug', $roleSlugs)
            ->pluck('id')
            ->mapWithKeys(fn ($id) => [$id => ['assigned_by' => $assignedBy?->id]])
            ->all();

        if (empty($roleIds)) {
            return;
        }

        $this->roles()->sync($roleIds);
    }

    private function syncLegacyRoleRecord(): void
    {
        if (
            blank($this->role)
            || ! $this->exists
            || ! Schema::hasTable('roles')
            || ! Schema::hasTable('role_user')
        ) {
            return;
        }

        Role::ensureDefaultsExist();

        $roleId = Role::query()
            ->where('slug', $this->role)
            ->value('id');

        if ($roleId === null) {
            return;
        }

        $this->roles()->syncWithoutDetaching([$roleId]);
    }
}
