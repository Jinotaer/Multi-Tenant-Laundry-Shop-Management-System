# Potential Errors & Issues in Multi-Tenant Laundry Shop Management System

## 🚨 Critical Errors

### 1. **PayMongo Integration Errors**

#### Error: Payment Checkout Fails
**Cause:**
- Invalid PayMongo credentials
- Expired API keys
- Network timeout
- PayMongo service down

**Impact:**
- Customer cannot pay online
- Order remains unpaid
- Poor customer experience

**Current Handling:**
```php
// OrderPaymentController.php
catch (\Throwable) {
    $payment->update(['status' => 'failed']);
    return redirect()->back()->with('error', 'Unable to create checkout session');
}
```

**Missing:**
- ❌ No validation of PayMongo credentials before saving
- ❌ No test connection button
- ❌ No detailed error messages to tenant
- ❌ No retry mechanism

---

#### Error: Webhook Not Received
**Cause:**
- Webhook URL not configured in PayMongo
- Firewall blocking webhooks
- PayMongo webhook signature mismatch

**Impact:**
- Payment completed but order not marked as paid
- Customer paid but system shows unpaid
- Manual reconciliation needed

**Missing:**
- ❌ No webhook handler implemented
- ❌ No manual payment verification button
- ❌ No payment reconciliation tool

---

#### Error: Tenant Uses Platform's PayMongo Keys
**Cause:**
- Tenant doesn't configure their own keys
- System falls back to platform default

**Impact:**
- Customer payments go to platform owner, not tenant
- Money routing issues
- Legal/financial problems

**Current Protection:**
```php
if (!tenant()->paymongo_secret_key) {
    return redirect()->back()->with('error', 'Online payments not available');
}
```

**Missing:**
- ❌ No warning when using default keys
- ❌ No admin notification

---

### 2. **Subscription Downgrade Errors**

#### Error: Pending Payments After Downgrade
**Cause:**
- Customer has pending PayMongo checkout
- Tenant downgrades to Starter
- Customer tries to complete payment

**Impact:**
- Payment may succeed but feature disabled
- Money goes to tenant but system can't process
- Orphaned payments

**Current Handling:**
```php
// Listener cancels pending payments
Payment::where('status', 'pending')->update(['status' => 'cancelled']);
```

**Missing:**
- ❌ Listener not registered in EventServiceProvider
- ❌ No customer notification about cancelled payment
- ❌ No refund mechanism

---

#### Error: Exceeding Limits After Downgrade
**Cause:**
- Tenant has 100+ customers
- Downgrades to Starter (limit: 50)
- Tries to add new customer

**Impact:**
- Cannot add new customers
- Confusing error message
- Business disruption

**Current Handling:**
- ❌ No limit checking implemented
- ❌ No validation in CustomerController
- ❌ No clear error message

**Should Have:**
```php
if (Customer::count() >= tenant()->subscriptionPlan->customer_limit) {
    return back()->with('error', 'Customer limit reached. Upgrade to Premium.');
}
```

---

### 3. **Multi-Guard Authentication Errors**

#### Error: Customer vs User Guard Confusion
**Cause:**
- System has 3 guards: admin, web, customer
- Auth checks may use wrong guard
- Session conflicts

**Impact:**
- Login failures
- Permission errors
- User logged out unexpectedly

**Current Issues:**
```php
// DashboardController.php
$user = auth()->guard('web')->user() ?? auth()->guard('customer')->user();
```

**Potential Problems:**
- ❌ Inconsistent guard usage across controllers
- ❌ Middleware may check wrong guard
- ❌ Session conflicts between guards

---

#### Error: Customer Cannot Access Portal
**Cause:**
- Customer logged in via 'customer' guard
- Route protected by 'web' guard
- Middleware mismatch

**Impact:**
- 403 Forbidden errors
- Customer cannot view orders
- Support tickets

**Current Protection:**
```php
Route::middleware('tenant.auth')->group(...) // Checks both guards
```

**Missing:**
- ❌ No clear error message explaining guard issue
- ❌ No automatic guard switching

---

### 4. **Tenant Database Errors**

#### Error: Tenant Database Not Created
**Cause:**
- Migration fails during tenant creation
- Database permissions issue
- Disk space full

**Impact:**
- Tenant cannot login
- 500 Internal Server Error
- Business cannot operate

**Current Handling:**
```php
// DemoTenantCustomerSeeder.php
if (!$this->tenantDatabaseExists($tenant)) {
    $tenant->delete();
    $tenant = null;
}
```

**Missing:**
- ❌ No automatic retry
- ❌ No admin notification
- ❌ No user-friendly error page

---

#### Error: Orphaned Tenant Database
**Cause:**
- Tenant record deleted
- Database not dropped
- Cleanup failed

**Impact:**
- Wasted disk space
- Database clutter
- Potential security issue

**Current Handling:**
```php
$this->dropOrphanedTenantDatabase($tenantId);
```

**Missing:**
- ❌ No scheduled cleanup job
- ❌ No admin tool to find orphaned databases

---

### 5. **Feature Access Errors**

#### Error: Accessing Disabled Feature
**Cause:**
- User bookmarked premium feature URL
- Downgrades to Starter
- Tries to access bookmarked URL

**Impact:**
- 403 Forbidden error
- Confusing for user
- Poor UX

**Current Handling:**
```php
Route::middleware('feature:online_payments')->group(...)
// Returns: 403 - This feature is not enabled for your shop
```

**Missing:**
- ❌ No redirect to upgrade page
- ❌ No explanation of which plan needed
- ❌ No "Upgrade to access" button

---

### 6. **Customer Model Permission Errors**

#### Error: hasPermission() Called on Customer
**Cause:**
- Layout checks permissions
- Customer model missing permission methods
- Method not found error

**Impact:**
- 500 Internal Server Error
- Customer cannot view pages
- System crash

**Current Fix:**
```php
// Customer.php
public function hasPermission(string $permissionKey): bool {
    return false;
}
```

**Potential Issues:**
- ❌ Other permission methods may be missing
- ❌ No comprehensive interface for user types

---

### 7. **Order Payment Errors**

#### Error: Order Already Paid
**Cause:**
- Customer clicks "Pay Online" multiple times
- Creates multiple payment sessions
- Confusion about payment status

**Impact:**
- Multiple checkout sessions
- Customer charged twice
- Refund needed

**Current Handling:**
```php
if ($order->isPaid()) {
    return redirect()->back()->with('error', 'Order already paid');
}
```

**Missing:**
- ❌ No check before creating payment
- ❌ Button not disabled after click
- ❌ No loading state

---

#### Error: Payment Webhook Race Condition
**Cause:**
- Customer completes payment
- Webhook arrives before redirect
- Order marked paid twice

**Impact:**
- Duplicate payment records
- Loyalty points awarded twice
- Data inconsistency

**Missing:**
- ❌ No webhook handler
- ❌ No idempotency checks
- ❌ No transaction locking

---

### 8. **Loyalty Program Errors**

#### Error: Loyalty Record Not Created
**Cause:**
- Customer created before loyalty feature enabled
- firstOrCreate() fails
- Database constraint error

**Impact:**
- Null pointer errors
- Dashboard crashes
- Customer cannot see loyalty

**Current Handling:**
```php
$loyalty = $customer->loyalty()->firstOrCreate([], [
    'points' => 0,
    'stamps' => 0,
    'tier' => 'bronze',
    'lifetime_spent' => 0,
]);
```

**Missing:**
- ❌ No error handling if creation fails
- ❌ No null checks before using $loyalty

---

#### Error: Negative Loyalty Points
**Cause:**
- Points redeemed
- Calculation error
- Manual adjustment

**Impact:**
- Negative balance displayed
- Customer confusion
- Cannot redeem rewards

**Missing:**
- ❌ No validation preventing negative points
- ❌ No minimum balance check

---

### 9. **File Upload Errors**

#### Error: Logo Upload Fails
**Cause:**
- File too large (>2MB)
- Invalid file type
- Disk space full
- Permission denied

**Impact:**
- Logo not saved
- Old logo remains
- Confusing error message

**Current Validation:**
```php
'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,svg', 'max:2048']
```

**Missing:**
- ❌ No user-friendly error messages
- ❌ No file size preview before upload
- ❌ No image optimization

---

#### Error: Logo File Orphaned
**Cause:**
- New logo uploaded
- Old logo not deleted
- Storage cleanup fails

**Impact:**
- Wasted disk space
- Storage costs increase

**Current Handling:**
```php
if ($tenant->logo_path && Storage::disk('public')->exists($tenant->logo_path)) {
    Storage::disk('public')->delete($tenant->logo_path);
}
```

**Missing:**
- ❌ No scheduled cleanup of orphaned files
- ❌ No storage usage monitoring

---

### 10. **Session & Cookie Errors**

#### Error: Session Expired During Payment
**Cause:**
- Customer starts payment
- Session expires (2 hours)
- Returns from PayMongo
- Not authenticated

**Impact:**
- Cannot complete payment
- Order not marked paid
- Customer frustration

**Missing:**
- ❌ No session extension during payment
- ❌ No payment completion without login
- ❌ No "continue as guest" option

---

### 11. **Tenant Identification Errors**

#### Error: Wrong Tenant Context
**Cause:**
- Subdomain misconfigured
- DNS not pointing correctly
- Tenant not found

**Impact:**
- 404 Not Found
- Cannot access shop
- Business down

**Current Handling:**
```php
Route::middleware([
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(...)
```

**Missing:**
- ❌ No friendly "Shop not found" page
- ❌ No suggestion to contact admin
- ❌ No automatic tenant creation

---

### 12. **Database Migration Errors**

#### Error: Central Migration Runs on Tenant DB
**Cause:**
- Migration placed in wrong folder
- Connection not specified
- Runs on tenant database

**Impact:**
- Tenant database corrupted
- Central tables in tenant DB
- Data integrity issues

**Protection:**
```php
// Central migrations: database/migrations/
// Tenant migrations: database/migrations/tenant/
```

**Missing:**
- ❌ No automated check for migration location
- ❌ No rollback mechanism

---

### 13. **Encryption Errors**

#### Error: Cannot Decrypt PayMongo Keys
**Cause:**
- APP_KEY changed
- Encryption key rotated
- Database restored from backup

**Impact:**
- Cannot read PayMongo credentials
- Online payments broken
- Must reconfigure

**Current Handling:**
```php
protected function casts(): array {
    return [
        'paymongo_secret_key' => 'encrypted',
        'paymongo_public_key' => 'encrypted',
    ];
}
```

**Missing:**
- ❌ No key rotation strategy
- ❌ No backup decryption method
- ❌ No warning before key change

---

### 14. **Concurrency Errors**

#### Error: Race Condition on Order Creation
**Cause:**
- Two staff create order simultaneously
- Order number collision
- Duplicate order numbers

**Impact:**
- Data integrity issues
- Confusion about orders
- Reporting errors

**Missing:**
- ❌ No database-level unique constraint
- ❌ No optimistic locking
- ❌ No transaction isolation

---

### 15. **Email Notification Errors**

#### Error: Email Sending Fails
**Cause:**
- SMTP credentials invalid
- Email service down
- Rate limit exceeded
- Invalid recipient email

**Impact:**
- Customer not notified
- Order status unknown
- Poor communication

**Missing:**
- ❌ No email queue retry
- ❌ No fallback notification method
- ❌ No admin alert on failure
- ❌ No email delivery tracking

---

## 🛠️ Recommended Fixes

### High Priority:
1. ✅ Add PayMongo credential validation
2. ✅ Implement webhook handler
3. ✅ Add customer/order limit checks
4. ✅ Register subscription change listener
5. ✅ Add payment idempotency

### Medium Priority:
6. ✅ Better error messages for feature access
7. ✅ Session management during payments
8. ✅ File upload error handling
9. ✅ Loyalty null checks
10. ✅ Guard consistency audit

### Low Priority:
11. ✅ Orphaned file cleanup
12. ✅ Database cleanup jobs
13. ✅ Email retry mechanism
14. ✅ Storage monitoring
15. ✅ Admin notification system

---

## 📊 Error Monitoring Recommendations

1. **Implement Logging:**
   - Log all PayMongo API calls
   - Log subscription changes
   - Log authentication failures

2. **Add Sentry/Bugsnag:**
   - Track exceptions in production
   - Get notified of errors
   - Monitor error rates

3. **Health Checks:**
   - Database connectivity
   - PayMongo API status
   - Storage availability
   - Email service status

4. **Admin Dashboard:**
   - Failed payments list
   - Orphaned databases
   - Tenants near limits
   - Error rate graphs

---

**Summary:** Most critical issues are around PayMongo integration, subscription limits, and multi-guard authentication. Implementing proper validation, error handling, and monitoring will prevent most issues.
