# Subscription Upgrade Feature

## Overview
This feature allows tenants to self-service upgrade their subscription plans through PayMongo payment integration. When a user clicks "Upgrade Now" on a premium plan, they are taken through a payment flow to complete the upgrade.

## Implementation

### 1. Controller: SubscriptionUpgradeController
**Location**: `app/Http/Controllers/Tenant/SubscriptionUpgradeController.php`

**Methods**:
- `show()` - Displays the upgrade page with plan comparison and details
- `checkout()` - Creates PayMongo checkout session for the upgrade payment
- `success()` - Handles successful payment and activates the new plan
- `upgradePlan()` - Updates tenant's plan and subscription expiration

**Key Features**:
- Validates plan selection (must be premium, not current plan)
- Creates payment record with type 'upgrade'
- Integrates with PayMongo for secure payment processing
- Automatically updates tenant's plan after successful payment

### 2. Routes
**Location**: `routes/tenant.php`

Added three new routes under owner-only middleware:
```php
Route::get('/subscription/upgrade', [SubscriptionUpgradeController::class, 'show'])
    ->name('tenant.subscription.upgrade');
Route::post('/subscription/upgrade/checkout', [SubscriptionUpgradeController::class, 'checkout'])
    ->name('tenant.subscription.upgrade.checkout');
Route::get('/subscription/upgrade/success', [SubscriptionUpgradeController::class, 'success'])
    ->name('tenant.subscription.upgrade.success');
```

### 3. Views

#### subscription-upgrade.blade.php
**Location**: `resources/views/tenant/subscription-upgrade.blade.php`

Displays:
- Plan comparison (current vs new plan)
- New plan details (price, billing cycle, limits)
- Feature list with benefits
- Payment button that submits to checkout
- Help section with support contact

#### subscription-upgrade-success.blade.php
**Location**: `resources/views/tenant/subscription-upgrade-success.blade.php`

Shows:
- Success confirmation message
- Payment details (plan, amount, dates)
- Links to dashboard and subscription page

#### subscription-plans.blade.php (Updated)
**Location**: `resources/views/tenant/subscription-plans.blade.php`

Changed "Upgrade Now" button to link to upgrade flow:
```php
<a href="{{ route('tenant.subscription.upgrade', ['plan' => $plan->id]) }}">
    Upgrade Now
</a>
```

### 4. Webhook Handler (Updated)
**Location**: `app/Http/Controllers/PayMongoWebhookController.php`

Added handling for upgrade payment type:
- Detects `payment_type === 'upgrade'`
- Updates tenant's `subscription_plan_id` to new plan
- Sets new expiration date based on billing cycle
- Activates tenant and resets renewal reminders

## Payment Flow

1. **User clicks "Upgrade Now"** on subscription plans page
2. **Upgrade page displays** with plan comparison and details
3. **User clicks payment button** → Creates payment record with type 'upgrade'
4. **Redirects to PayMongo** checkout session
5. **User completes payment** on PayMongo
6. **Returns to success page** → Verifies payment status
7. **Plan is upgraded** → Tenant's plan_id and expiration updated
8. **Webhook confirms** → Processes payment asynchronously

## Payment Types

The system now supports three payment types:
- `subscription` - Initial subscription payment
- `renewal` - Renewing current plan (extends expiration)
- `upgrade` - Changing to a different plan (changes plan_id + extends expiration)

## Database

Uses existing `payments` table with `payment_type` column:
- `payment_type` = 'upgrade'
- `subscription_plan_id` = new plan ID
- `tenant_id` = tenant being upgraded

## Security

- Owner-only access (middleware protected)
- Plan validation (must be premium, not current)
- Payment verification via PayMongo API
- Webhook signature verification
- Tenant isolation (can only upgrade own subscription)

## User Experience

### Before Upgrade
- User sees current plan and available plans
- Premium plans show "Upgrade Now" button
- Free plans show "Contact Admin to Downgrade"

### During Upgrade
- Clear plan comparison
- Feature list with benefits
- Secure PayMongo payment gateway
- Support for multiple payment methods (GCash, GrabPay, Cards, PayMaya)

### After Upgrade
- Immediate plan activation
- New expiration date set
- Success confirmation with details
- Access to new plan features

## Testing Checklist

- [ ] Upgrade from Free to Premium plan
- [ ] Upgrade from one Premium plan to another
- [ ] Cannot upgrade to same plan (redirects)
- [ ] Cannot upgrade to free plan (validation)
- [ ] Payment success updates plan correctly
- [ ] Expiration date calculated correctly (monthly/yearly)
- [ ] Webhook processes upgrade payments
- [ ] Success page displays correct information
- [ ] Dashboard reflects new plan immediately

## Future Enhancements

- Prorated pricing for mid-cycle upgrades
- Downgrade flow (with admin approval)
- Plan comparison tool
- Upgrade history/audit log
- Email notifications for upgrades
