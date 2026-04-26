<?php

namespace App\Models;

use App\Notifications\CustomerResetPasswordNotification;
use App\Notifications\CustomerSetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'role',
        'layout_preferences',
        'notes',
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
     * Send the customer-specific password setup notification.
     */
    public function sendPasswordResetNotification($token): void
    {
        $url = route('tenant.password.reset', [
            'token' => $token,
            'email' => $this->getEmailForPasswordReset(),
        ]);

        $shopName = tenant()?->data['shop_name'] ?? config('app.name');

        $this->notify(new CustomerResetPasswordNotification($url, $shopName));
    }

    public function isOwner(): bool
    {
        return false;
    }

    public function isStaff(): bool
    {
        return false;
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    public function hasPermission(string $permissionKey): bool
    {
        return false;
    }

    public function hasAnyPermission(array $permissionKeys): bool
    {
        return false;
    }

    /**
     * Get all orders for this customer.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the loyalty account for the customer.
     */
    public function loyalty(): HasOne
    {
        return $this->hasOne(CustomerLoyalty::class);
    }
}
