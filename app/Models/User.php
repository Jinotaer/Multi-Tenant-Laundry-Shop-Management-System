<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

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

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
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

        if (! $this->isStaff()) {
            return false;
        }

        return $this->permissions()
            ->where('key', $permissionKey)
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

        if (! $this->hasPermission('permissions.manage')) {
            return false;
        }

        if ($permissionKey === 'permissions.manage') {
            return false;
        }

        return $this->hasPermission($permissionKey);
    }
}
