# ⚡ QUICK FIX - Shipping Not Working

## 🎯 Problem
Alamat ga bisa calculate shipping → kotak kuning error

## ✅ Solution (2 Menit!)

### Step 1: Open Browser Console
1. Buka checkout page
2. Tekan **F12** (DevTools)
3. Pilih tab **Console**

### Step 2: Select Address
1. Pilih address dari dropdown
2. Lihat di console, muncul log:

```javascript
Address selected: {
  hasGPS: false,      // ❌ INI MASALAHNYA!
  hasPostal: false,   // ❌ INI JUGA!
  ...
}
```

### Step 3: Run Auto-Fix
Open di browser:
```
http://your-site.com/fix-addresses-geocode.php
```

Ini akan:
- Detect city dari address text
- Set default GPS coordinates
- Extract/add postal codes
- Update database otomatis

### Step 4: Test Lagi
1. Refresh checkout page
2. Pilih address lagi
3. Console should show:
```javascript
Address selected: {
  hasGPS: true,      // ✅ FIXED!
  hasPostal: true,   // ✅ FIXED!
  ...
}

🚚 Fetching shipping rates with: {...}
```

4. Shipping options muncul! 🎉

---

## 🔥 Manual Fix (Kalau Auto-Fix Ga Work)

### Option A: Via Address Book
1. Go to `/member/address-book.php`
2. Edit address yang bermasalah
3. **Add postal code:** Contoh Jakarta = `10110`
4. **Click map** untuk set GPS
5. Save

### Option B: Database Direct
```sql
-- Contoh: Set Jakarta postal + GPS
UPDATE user_addresses
SET postal_code = '10110',
    latitude = -6.2088,
    longitude = 106.8456
WHERE id = [ADDRESS_ID];
```

---

## 📍 Common Postal Codes

| City | Postal |
|------|--------|
| Jakarta Pusat | 10110 |
| Jakarta Selatan | 12110 |
| Medan | 20111 |
| Binjai | 20719 |
| Bandung | 40111 |
| Surabaya | 60119 |
| Bekasi | 17111 |
| Tangerang | 15111 |

---

## ✅ Verify It's Fixed

Console should show:
```javascript
✅ Address selected with location data
✅ 🚚 Fetching shipping rates...
✅ Shipping rates response: { success: true, rates: [...] }
```

UI should show:
- **JNE** Regular/Express
- **J&T** Express
- **SiCepat** REG/BEST
- **AnterAja**
- And more!

**DONE!** 🚀

---

## 🆘 Still Not Working?

1. Check error logs:
   ```bash
   tail -f /path/to/error.log | grep "Calculate Rates"
   ```

2. Run debug script:
   ```
   http://your-site.com/debug-user-addresses.php
   ```

3. Check Biteship API key di system_settings

4. Contact support dengan screenshot console logs
