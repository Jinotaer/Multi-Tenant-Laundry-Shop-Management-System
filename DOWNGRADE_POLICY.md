# What Happens When Tenant Downgrades from Premium to Starter?

## 📊 Plan Comparison

### Starter Plan (Free)
- **Staff Limit:** 1 owner only
- **Customer Limit:** 50 customers
- **Order Limit:** 100 orders per month
- **Features:**
  - ✅ Basic order tracking
  - ✅ Simple pricing
  - ✅ Customer portal

### Premium Plan (₱2,500/month)
- **Staff Limit:** Unlimited
- **Customer Limit:** Unlimited
- **Order Limit:** Unlimited
- **Features:**
  - ✅ Everything in Starter
  - ✅ Advanced workflow
  - ✅ Advanced pricing
  - ✅ Email notifications
  - ✅ Reports & analytics
  - ✅ Expense tracking
  - ✅ Customer loyalty program
  - ✅ Custom branding (logo)
  - ✅ Online payments (PayMongo)
  - ✅ SMS notifications
  - ✅ Inventory management
  - ✅ Priority support

---

## 🔄 What Happens During Downgrade

### ✅ **DATA IS PRESERVED** (Nothing is Deleted)

All tenant data remains in the database:
- ✅ All customers (even if over 50)
- ✅ All orders (even if over 100)
- ✅ All staff accounts
- ✅ All services
- ✅ All expenses
- ✅ All loyalty points
- ✅ All payment records
- ✅ PayMongo credentials
- ✅ Custom logo
- ✅ All historical data

### 🔒 **FEATURES ARE RESTRICTED** (Access Blocked)

Premium features become inaccessible:

#### 1. **Staff Management**
- ❌ Cannot add new staff (limit: 1 owner)
- ✅ Existing staff accounts remain but cannot login
- ✅ Data preserved for when they upgrade

#### 2. **Customer Limit**
- ❌ Cannot add customers beyond 50
- ✅ Existing customers (51+) remain in database
- ✅ Can still view all customers
- ⚠️ Cannot create new customers if at limit

#### 3. **Order Limit**
- ❌ Cannot create orders beyond 100/month
- ✅ All existing orders remain accessible
- ✅ Historical orders preserved
- ⚠️ Counter resets monthly

#### 4. **Online Payments**
- ❌ "Pay Online" buttons hidden from customers
- ❌ Payment Settings page inaccessible
- ✅ PayMongo credentials saved (encrypted)
- ✅ Past payment records preserved
- ⚠️ Pending payments cancelled

#### 5. **Customer Loyalty**
- ❌ Loyalty section hidden from customer dashboard
- ❌ Cannot earn new points/stamps
- ✅ Existing points/stamps preserved
- ✅ Tier levels saved
- ⚠️ Cannot redeem rewards

#### 6. **Expense Tracking**
- ❌ Expenses page inaccessible (403 error)
- ✅ All expense records preserved
- ✅ Data available when upgraded

#### 7. **Reports & Analytics**
- ❌ Reports page inaccessible
- ❌ Analytics dashboard inaccessible
- ✅ Data continues to be tracked
- ✅ Available immediately on upgrade

#### 8. **Custom Branding**
- ❌ Cannot upload/change logo
- ❌ Logo settings hidden
- ✅ Existing logo preserved
- ✅ Logo still displays if already uploaded

#### 9. **Inventory Management**
- ❌ Inventory page inaccessible
- ✅ All inventory records preserved
- ✅ Stock levels maintained

#### 10. **Priority Support**
- ❌ Support ticket system inaccessible
- ❌ Cannot create new tickets
- ✅ Existing tickets preserved

#### 11. **Notifications**
- ❌ Email notifications disabled
- ❌ SMS notifications disabled
- ✅ Notification history preserved

---

## 🎯 User Experience After Downgrade

### For Tenant Owner:
```
✅ Can still login
✅ Can manage customers (up to 50)
✅ Can create orders (up to 100/month)
✅ Can manage services
✅ Can view all historical data
❌ Cannot access premium features
❌ Cannot add staff
❌ Cannot accept online payments
❌ Cannot view reports/analytics
```

### For Staff Members:
```
❌ Cannot login (staff limit: 1)
✅ Account preserved
✅ Can login again when upgraded
```

### For Customers:
```
✅ Can still login to customer portal
✅ Can view their orders
✅ Can see order history
❌ Cannot pay online
❌ Cannot see loyalty rewards
❌ Must pay via cash/bank transfer
```

---

## 🔄 What Happens When They Upgrade Back to Premium?

### Instant Restoration:
1. ✅ All features immediately accessible
2. ✅ Staff can login again
3. ✅ Online payments work (PayMongo already configured)
4. ✅ Loyalty points visible and active
5. ✅ Reports show all historical data
6. ✅ No data loss or reconfiguration needed

---

## 💾 Data Retention Policy

### Permanently Stored:
- All customer records
- All order history
- All payment records
- All expense records
- All loyalty data
- PayMongo credentials (encrypted)
- Custom logo files
- All staff accounts

### Never Deleted:
- Nothing is automatically deleted during downgrade
- Tenant must manually delete data if desired
- All data available for upgrade

---

## ⚠️ Important Notes

1. **No Data Loss:** Downgrading does NOT delete any data
2. **Feature Access Only:** Only access to premium features is restricted
3. **Limits Enforced:** Cannot exceed Starter plan limits (50 customers, 100 orders/month)
4. **Reversible:** Upgrading restores all features instantly
5. **Graceful Degradation:** System handles downgrade smoothly without errors

---

## 🛡️ Technical Implementation

### Middleware Protection:
```php
// Routes protected by feature middleware
Route::middleware('feature:online_payments')->group(...)
Route::middleware('feature:expense_tracking')->group(...)
Route::middleware('feature:reports')->group(...)
```

### View-Level Checks:
```blade
@if (tenant()->hasFeature('customer_loyalty'))
    {{-- Show loyalty section --}}
@endif
```

### Database:
- All data remains in tenant database
- Features array updated on tenant record
- No data deletion queries executed

---

## 📞 Support Recommendations

When tenant downgrades, inform them:
1. ✅ All data is safe and preserved
2. ✅ Can upgrade anytime to restore features
3. ✅ No reconfiguration needed when upgrading
4. ⚠️ Premium features will be inaccessible
5. ⚠️ Customers cannot pay online during downgrade
6. ⚠️ Staff members cannot login (except owner)

---

**Summary:** Downgrading is SAFE. It only restricts access to premium features. All data is preserved and instantly available when they upgrade again.
