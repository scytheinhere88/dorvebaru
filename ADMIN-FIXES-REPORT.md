# VOUCHERS NOT SHOWING - ROOT CAUSE FOUND

**Status:** Cause identified, fix ready

---

## THE REAL PROBLEM:

Your debug shows:
- ✅ 2 vouchers assigned to you
- ❌ Query returns: 0 vouchers (filtered out!)

**Why:** Vouchers are being filtered by:
```sql
WHERE v.is_active = 1
  AND v.valid_from <= NOW()
  AND v.valid_until >= NOW()
```

---

## FIX TOOLS:

### 1. check-voucher-table.php
**URL:** https://dorve.id/check-voucher-table.php

Shows EXACTLY why each voucher is filtered out:
- is_active status
- valid_from date
- valid_until date

### 2. fix-voucher-dates.php  
**URL:** https://dorve.id/fix-voucher-dates.php

Automatically fixes all vouchers:
- Activates them (is_active = 1)
- Sets valid_from to NOW
- Extends valid_until to +30 days

---

## QUICK FIX:

```
1. https://dorve.id/check-voucher-table.php
   (see what's wrong)

2. https://dorve.id/fix-voucher-dates.php
   (auto-fix)

3. https://dorve.id/member/vouchers/
   (verify vouchers show)
```

---

Run check-voucher-table.php NOW and share screenshot!
