# 🔍 DEBUG GUIDE - VOUCHERS & CHECKOUT ISSUES

**Date:** 2025-12-08
**Status:** ✅ FIXES APPLIED + DEBUG TOOLS READY

---

## 🚨 CURRENT ISSUES

### 1. ❌ VOUCHERS NOT SHOWING IN MEMBER ACCOUNT

**Problem:**
- Vouchers assigned successfully (14 assignments)
- But member vouchers page shows "No Discount Vouchers Available"

**Root Cause:**
```php
// OLD QUERY (WRONG):
FROM vouchers v
LEFT JOIN user_vouchers uv ON v.id = uv.voucher_id AND uv.user_id = ?
WHERE v.is_active = 1
```
**Issue:** LEFT JOIN shows ALL vouchers, not just assigned ones!

**Fix Applied:**
```php
// NEW QUERY (CORRECT):
FROM user_vouchers uv
INNER JOIN vouchers v ON uv.voucher_id = v.id
WHERE uv.user_id = ?
  AND v.is_active = 1
```
**Result:** Now only shows vouchers in `user_vouchers` table ✅

---

### 2. ❌ CHECKOUT STILL ERROR 500

**Problem:**
- header.php fixed (1.7MB → 18KB)
- But checkout page still returns error 500

**Possible Causes:**
1. Database query timeout
2. Memory limit exceeded
3. Infinite redirect loop
4. JavaScript error
5. Server configuration (.htaccess)
6. PHP fatal error in included file

**Debug Tools Created:**
- `debug-checkout.php` - Basic diagnostics
- `test-checkout-detailed.php` - Step-by-step simulation

---

## 🛠️ DEBUG TOOLS AVAILABLE

### 1. **debug-vouchers.php** 🎫
**URL:** `https://dorve.id/debug-vouchers.php`

**What It Does:**
- ✅ Checks if `user_vouchers` table exists
- ✅ Shows how many vouchers assigned to YOU
- ✅ Compares OLD query vs NEW query results
- ✅ Shows exactly which vouchers you have
- ✅ Explains why vouchers not showing

**When to Use:**
- Vouchers not appearing in member account
- Want to see which vouchers are assigned
- Need to verify fix is working

---

### 2. **debug-checkout.php** 🛒
**URL:** `https://dorve.id/debug-checkout.php`

**What It Does:**
- ✅ Tests if checkout.php can load
- ✅ Checks header.php file size
- ✅ Verifies all required files exist
- ✅ Shows PHP error log
- ✅ Tests config.php connection

**When to Use:**
- Checkout shows error 500
- Need to find which file is broken
- Want to see PHP errors

---

### 3. **test-checkout-detailed.php** 🔬
**URL:** `https://dorve.id/test-checkout-detailed.php`

**What It Does:**
- ✅ Simulates ENTIRE checkout page load
- ✅ Tests each step individually (13 steps)
- ✅ Shows execution time for each step
- ✅ Identifies exact failure point
- ✅ Shows memory usage

**When to Use:**
- Checkout fails but don't know why
- Need to find EXACT error location
- Want detailed performance metrics

---

### 4. **fix-voucher-system.php** 🎯
**URL:** `https://dorve.id/fix-voucher-system.php`

**What It Does:**
- ✅ Creates `user_vouchers` table
- ✅ Auto-assigns tier vouchers
- ✅ Auto-assigns "all users" vouchers
- ✅ Shows detailed assignment report

**When to Use:**
- First time setting up vouchers
- Want to re-assign all vouchers
- Vouchers not in database

---

## 📋 TESTING CHECKLIST

### ✅ STEP 1: Test Voucher Fix

1. **Login as member:**
   ```
   URL: https://dorve.id/auth/login.php
   Use any member account
   ```

2. **Run debug tool:**
   ```
   URL: https://dorve.id/debug-vouchers.php
   ```

3. **Check results:**
   - ✅ user_vouchers table exists?
   - ✅ Vouchers assigned to you?
   - ✅ New query shows vouchers?

4. **View vouchers page:**
   ```
   URL: https://dorve.id/member/vouchers/
   ```

5. **Expected result:**
   - Should show 2 vouchers (DISKONBARU26, DISKON2026)
   - Both are "all users" vouchers
   - Should have details and "Copy" button

---

### ✅ STEP 2: Test Checkout Fix

1. **Run basic debug:**
   ```
   URL: https://dorve.id/debug-checkout.php
   ```

2. **Check file sizes:**
   - header.php should be < 50KB ✅
   - checkout.php should be ~53KB ✅

3. **Run detailed test:**
   ```
   URL: https://dorve.id/test-checkout-detailed.php
   ```

4. **Check all steps pass:**
   - Step 1: Config loading ✅
   - Step 2: Session check ✅
   - Step 3: Login status ✅
   - Step 4: User data ✅
   - Step 5: Addresses ✅
   - Step 6: Cart items ✅
   - Step 7: Stock validation ✅
   - Step 8: Bank accounts ✅
   - Step 9: Page metadata ✅
   - Step 10: Header include ✅
   - Step 11: CSS processing ✅
   - Step 12: HTML rendering ✅
   - Step 13: Footer include ✅

5. **Try real checkout:**
   ```
   URL: https://dorve.id/pages/checkout.php
   ```

6. **If still error 500:**
   - Check browser console (F12 → Console)
   - Check browser Network tab (F12 → Network)
   - Look for HTTP response code
   - Check for JavaScript errors

---

## 🔧 FIXES APPLIED

### 1. ✅ Voucher Query Fixed

**File:** `/member/vouchers/index.php`

**Change:**
```diff
- FROM vouchers v
- LEFT JOIN user_vouchers uv ON v.id = uv.voucher_id AND uv.user_id = ?
+ FROM user_vouchers uv
+ INNER JOIN vouchers v ON uv.voucher_id = v.id
+ WHERE uv.user_id = ?
```

**Impact:** Only shows vouchers that are assigned to user

---

### 2. ✅ Header.php Indentation Fixed

**File:** `/includes/header.php`

**Before:**
- Size: 1,758,556 bytes (1.7MB)
- Problem: Excessive whitespace indentation

**After:**
- Size: 17,483 bytes (18KB)
- Fixed: Removed all leading whitespace

**Command used:**
```bash
sed 's/^[[:space:]]*//' header.php.backup > header.php
```

---

### 3. ✅ Edit Voucher Layout Fixed

**File:** `/admin/vouchers/edit.php`

**Before:**
- 326 lines with inline HTML/CSS
- No admin header

**After:**
- Uses `admin-header.php` and `admin-footer.php`
- Professional admin design
- Consistent with other admin pages

---

### 4. ✅ Product Image Query Updated

**File:** `/admin/products/index.php`

**Before:**
```sql
SELECT p.*, p.image FROM products p
```

**After:**
```sql
SELECT p.*,
  (SELECT image_path FROM product_images
   WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
FROM products p
```

**Impact:** Uses new `product_images` table, shows primary image

---

## 🎯 EXPECTED BEHAVIOR

### After Voucher Fix:

**Member Vouchers Page:**
```
/member/vouchers/

💰 Discount Vouchers [2]
┌──────────────────────────────────┐
│ 💰 DISKONBARU26                   │
│ DISKONBARU26                      │
│ [discount value]                  │
│ Valid until: [date]               │
│ [📋 Copy]                         │
└──────────────────────────────────┘

┌──────────────────────────────────┐
│ 💰 DISKON2026                     │
│ DISKON2026                        │
│ [discount value]                  │
│ Valid until: [date]               │
│ [📋 Copy]                         │
└──────────────────────────────────┘
```

---

### After Checkout Fix:

**Checkout Page:**
```
/pages/checkout.php

✅ Page loads without error 500
✅ Shows shipping form
✅ Shows payment methods
✅ Shows order summary
✅ All functionality works
```

---

## 🚨 TROUBLESHOOTING

### If vouchers still not showing:

1. **Check if table exists:**
   ```sql
   SHOW TABLES LIKE 'user_vouchers';
   ```

2. **Check if vouchers assigned:**
   ```sql
   SELECT COUNT(*) FROM user_vouchers WHERE user_id = [YOUR_USER_ID];
   ```

3. **Run fix script again:**
   ```
   https://dorve.id/fix-voucher-system.php
   ```

4. **Clear browser cache:**
   - Ctrl+F5 (Windows/Linux)
   - Cmd+Shift+R (Mac)

---

### If checkout still error 500:

1. **Check header.php size:**
   ```bash
   ls -lh includes/header.php
   # Should be ~18KB, not 1.7MB
   ```

2. **Check PHP error log:**
   - Look in server error log
   - Enable display_errors temporarily

3. **Check memory limit:**
   ```php
   ini_get('memory_limit');
   # Should be at least 128M
   ```

4. **Check .htaccess:**
   - Look for rewrite rules
   - Check if mod_rewrite enabled

5. **Try direct access:**
   ```
   https://dorve.id/test-checkout-detailed.php
   ```
   If this works but checkout.php doesn't, there's a specific issue in checkout.php

6. **Check browser console:**
   - Press F12
   - Go to Console tab
   - Look for JavaScript errors

7. **Check browser network:**
   - Press F12
   - Go to Network tab
   - Reload page
   - Click on checkout.php request
   - Look at Response tab

---

## 📊 CURRENT STATUS

| Component | Status | Next Action |
|-----------|--------|-------------|
| **Voucher Query** | ✅ FIXED | Test with real user |
| **Header.php** | ✅ FIXED | Verify size is 18KB |
| **Checkout Error** | ⚠️ NEEDS TEST | Run debug tools |
| **Product Images** | ⚠️ NEED UPLOAD | Re-upload images |
| **Edit Voucher** | ✅ FIXED | Working |

---

## 🎉 SUCCESS METRICS

After all fixes work:

- ✅ Vouchers appear in member account
- ✅ Can copy voucher codes
- ✅ Checkout page loads without error
- ✅ Can complete checkout process
- ✅ Admin pages have consistent design
- ✅ Product images display correctly

---

## 📞 SUPPORT COMMANDS

### Quick Testing:
```bash
# 1. Test vouchers
https://dorve.id/debug-vouchers.php

# 2. Test checkout basic
https://dorve.id/debug-checkout.php

# 3. Test checkout detailed
https://dorve.id/test-checkout-detailed.php

# 4. Fix vouchers
https://dorve.id/fix-voucher-system.php

# 5. Check images
https://dorve.id/fix-admin-images.php
```

---

## 🔄 NEXT STEPS

1. **RUN:** `debug-vouchers.php` → Verify fix is working
2. **TEST:** Go to `/member/vouchers/` → See if vouchers appear
3. **RUN:** `test-checkout-detailed.php` → Find checkout issue
4. **TEST:** Go to `/pages/checkout.php` → See if error 500 fixed
5. **IF WORKS:** Test full checkout flow
6. **IF NOT:** Share debug tool results for further investigation

---

**DEBUGGING TOOLS READY!**
**RUN THEM NOW TO FIND THE EXACT ISSUES!** 🚀

---

**Generated:** 2025-12-08
**Status:** ✅ All fixes applied, ready for testing
