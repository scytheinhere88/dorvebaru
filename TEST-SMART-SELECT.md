# 🧪 QUICK TEST GUIDE - Smart Auto-Select

## 🚀 Test in 5 Minutes

### Pre-requisites
- User account created and logged in
- At least 1 free shipping voucher in database
- Products in cart totaling >= Rp500K

---

## Test 1: Auto Discount Only (No Voucher)

**Setup:**
```
Cart total: Rp600,000
Shipping: Rp30,000
No voucher applied
```

**Expected Result:**
- ✅ Shows "🎉 Free Shipping Promo -Rp30.000"
- ✅ Shipping shows strikethrough with "FREE!"
- ✅ Total = Rp600,000
- ✅ Console shows: "Using AUTO free shipping: 30000"

**How to Test:**
1. Add products to cart >= Rp500K
2. Go to checkout
3. Select address and shipping
4. Check summary sidebar
5. Open console (F12) to see logs

---

## Test 2: Voucher is Better

**Setup:**
```
Cart total: Rp700,000
Shipping: Rp60,000
Apply voucher: FREESHIP50 (max Rp50K)
```

**Expected Result:**
- ✅ Shows "🎟️ Free Shipping Voucher -Rp50.000"
- ✅ Shows "Save Rp15,000 more than auto! 🎯"
- ✅ Shipping shows: ~~Rp60,000~~ FREE!
- ✅ Total = Rp710,000 (700K + 10K remaining shipping)
- ✅ Console shows: "SMART SELECT: Voucher is better!"

**How to Test:**
1. Cart >= Rp500K
2. Select shipping that costs >= Rp50K
3. Click "Apply Vouchers"
4. Select free shipping voucher with max >= Rp50K
5. Click "Apply Vouchers"
6. Check summary and console

---

## Test 3: Auto is Better

**Setup:**
```
Cart total: Rp600,000
Shipping: Rp30,000
Apply voucher: FREESHIP20 (max Rp20K)
```

**Expected Result:**
- ✅ Shows "🎉 Free Shipping Promo -Rp30.000"
- ✅ Shows "Auto promo is better! 🎯"
- ✅ Auto discount used (Rp30K) instead of voucher (Rp20K)
- ✅ Total = Rp600,000
- ✅ Console shows: "SMART SELECT: Auto is better!"

**How to Test:**
1. Cart >= Rp500K
2. Select shipping Rp30K
3. Apply free shipping voucher with max < Rp30K
4. System should use auto discount
5. Voucher still shows in applied list but not used

---

## Test 4: Voucher Unlimited (Papua/Remote Area)

**Setup:**
```
Cart total: Rp500,000
Shipping: Rp150,000
Apply voucher: FREESHIP-PAPUA (unlimited)
```

**Expected Result:**
- ✅ Shows "🎟️ Free Shipping Voucher -Rp150.000"
- ✅ Shows "Save Rp115,000 more than auto! 🎯"
- ✅ Shipping: ~~Rp150,000~~ FREE!
- ✅ Total = Rp500,000
- ✅ Console shows: "SMART SELECT: Voucher is better!"

---

## Test 5: Below Minimum (No Auto)

**Setup:**
```
Cart total: Rp400,000
Shipping: Rp40,000
Apply voucher: FREESHIP30 (max Rp30K)
```

**Expected Result:**
- ✅ Shows "🎟️ Free Shipping Voucher -Rp30.000"
- ✅ Shows "Voucher Applied! 🎉"
- ✅ Total = Rp410,000 (400K + 10K remaining shipping)
- ✅ Console shows: "Using VOUCHER free shipping: 30000"

---

## Test 6: Complete Order Test

**Setup:**
```
Cart: Rp700,000
Shipping: Rp60,000
Voucher: FREESHIP50
```

**Steps:**
1. Complete checkout with bank transfer
2. Check order in admin panel
3. Verify order details:
   - Subtotal: Rp700,000
   - Shipping: Rp10,000 (60K - 50K)
   - Total: Rp710,000

**Expected Database:**
```sql
SELECT
  order_number,
  subtotal,
  shipping_cost,
  discount,
  total,
  voucher_codes
FROM orders
WHERE order_number = 'ORD-XXXXXXX'
```

Should show:
```
subtotal: 700000
shipping_cost: 10000
discount: 0
total: 710000
voucher_codes: "FREESHIP50"
```

---

## 📊 Quick Verification Table

| Scenario | Cart | Ship | Auto | Voucher | Selected | Final Ship | Total |
|----------|------|------|------|---------|----------|------------|-------|
| Auto only | 600K | 30K | 30K | - | Auto | 0 | 600K |
| Voucher better | 700K | 60K | 35K | 50K | Voucher | 10K | 710K |
| Auto better | 600K | 30K | 30K | 20K | Auto | 0 | 600K |
| Unlimited voucher | 500K | 150K | 35K | Unlimited | Voucher | 0 | 500K |
| Below min | 400K | 40K | 0 | 30K | Voucher | 10K | 410K |
| No discount | 300K | 20K | 0 | - | None | 20K | 320K |

---

## 🐛 Debugging Checklist

If something doesn't work:

### Check Frontend (F12 Console)
```javascript
// Should see logs like:
✅ Using AUTO free shipping: 35000
✅ Using VOUCHER free shipping: 50000
💎 SMART SELECT: Voucher is better!
📊 Final Calculation: {...}
```

### Check Backend (PHP Error Log)
```bash
# Look for:
Order Calculation: Subtotal=700000, Shipping=60000, ShipDiscount=50000, FinalShip=10000, VoucherDiscount=0, Total=710000
```

### Common Issues

**Issue**: Voucher not being used even when better
- Check: Is auto discount >= voucher?
- Check: Console logs show comparison

**Issue**: Wrong shipping amount charged
- Check: Backend logs show correct calculation
- Check: Database has correct `shipping_cost`

**Issue**: UI shows wrong discount source
- Check: `freeShippingSource` variable in console
- Check: Display logic in line 1873-1908

---

## ✅ All Tests Pass?

If all tests above work correctly, the system is functioning as designed!

**Next Steps:**
- Test with real vouchers in database
- Test order completion flow
- Test with multiple users
- Monitor customer feedback

---

## 🎓 Understanding the Console Logs

### Example Log Breakdown

```javascript
📊 Final Calculation: {
  subtotal: 700000,          // Cart items total
  shipping: 60000,           // Original shipping cost
  autoFreeShip: 35000,       // Auto discount (max 35K)
  voucherFreeShip: 50000,    // Voucher discount (from FREESHIP50)
  selectedFreeShip: 50000,   // SELECTED (voucher is better!)
  source: "voucher",         // Which one was used
  discount: 0,               // Cart discount (separate)
  total: 710000              // Final: 700K + (60K - 50K)
}
```

**Why 710K?**
- Subtotal: Rp700,000
- Shipping: Rp60,000
- Free shipping discount: -Rp50,000
- Remaining shipping: Rp10,000
- **Total: Rp710,000** ✅

---

## 🎯 Success Indicators

System is working correctly if:

1. ✅ User ALWAYS gets the best discount
2. ✅ Business NEVER pays double discount
3. ✅ UI clearly shows which discount is active
4. ✅ Console logs show accurate calculations
5. ✅ Order totals match expectations
6. ✅ Database shows correct amounts

**All green? You're good to go!** 🚀
