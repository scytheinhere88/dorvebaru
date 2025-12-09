# 🚀 BITESHIP SHIPPING INTEGRATION SETUP

## ✅ LANGKAH SETUP (WAJIB!)

### Step 1: Jalankan Database Setup (Jika belum)
Buka browser dan akses:
```
https://dorve.id/setup-biteship-database.php
```

Ini akan create tables yang dibutuhkan untuk Biteship.

### Step 2: Configure Biteship API & Store Address
Buka browser dan akses:
```
https://dorve.id/configure-biteship.php
```

Script ini akan:
- ✅ Setup API Key Biteship (LIVE)
- ✅ Setup alamat toko Binjai
- ✅ Activate Biteship shipping
- ✅ Test API connection

### Step 3: Test Checkout
1. Login ke website
2. Tambah produk ke cart
3. Checkout
4. Isi alamat pengiriman
5. Shipping options akan muncul otomatis dari Biteship!

---

## 📋 CONFIGURATION DETAILS

### API Key (LIVE)
```
biteship_live.eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJuYW1lIjoiRG9ydmUuaWQiLCJ1c2VySWQiOiI2OTI4NDVhNDM4MzQ5ZjAyZjdhM2VhNDgiLCJpYXQiOjE3NjQ2NTYwMjV9.xmkeeT2ghfHPe7PItX5HJ0KptlC5xbIhL1TlHWn6S1U
```

### Store Address (Origin)
```
Gang Hello Ponsel, Jl. Anggur Lk. VII No.43C
Bandar Senembah, Kec. Binjai Bar.
Kota Binjai, Sumatera Utara 20719
```

### Supported Couriers
- JNE
- J&T Express (JNT)
- SiCepat
- AnterAja
- ID Express
- Ninja Express

---

## 🧪 TESTING

### Manual Test via Checkout:
1. Add product to cart
2. Go to checkout
3. Enter destination address with postal code
4. Shipping options akan calculate otomatis!

### Check Logs:
Jika ada error, cek di:
- Browser Console (F12)
- Server error logs
- API response di Network tab

---

## ⚙️ ADMIN SETTINGS (Optional)

Jika mau update settings manual, akses:
```
/admin/settings/api-settings.php
```

---

## 🔧 TROUBLESHOOTING

### Problem: "Biteship API key not configured"
**Solution:** Run `configure-biteship.php` lagi

### Problem: "No couriers available"
**Solution:**
- Check postal code valid (5 digits)
- Check API key benar
- Check origin postal code 20719

### Problem: Shipping not showing up
**Solution:**
1. Open browser console (F12)
2. Check Network tab untuk API call ke `/api/shipping/calculate-rates.php`
3. Look at Response untuk error message
4. Check error_log file di server

---

## 📝 FILES MODIFIED

✅ `/includes/BiteshipConfig.php` - Updated dengan fallback ke Binjai
✅ `/includes/BiteshipClient.php` - Biteship API client
✅ `/api/shipping/calculate-rates.php` - Updated origin ke Binjai
✅ `/configure-biteship.php` - Setup script (NEW!)

---

## ✨ FEATURES

- Real-time shipping calculation
- Multiple courier options
- Automatic weight calculation
- Price comparison (sorted by cheapest)
- Support for variants & quantities
- Production API (LIVE)

---

## 🚨 IMPORTANT NOTES

1. API Key sudah PRODUCTION mode (bukan sandbox)
2. Charges akan real ke account Biteship lu
3. Origin postal code: **20719** (Binjai)
4. Pastikan products punya weight setting yang benar

---

## ✅ CHECKLIST

- [ ] Jalankan setup-biteship-database.php
- [ ] Jalankan configure-biteship.php
- [ ] Test API connection (akan auto-test di configure script)
- [ ] Test checkout dengan real address
- [ ] Verify shipping rates muncul
- [ ] Test complete order flow

---

## 🎯 NEXT STEPS AFTER SETUP

1. **Delete setup files** (for security):
   - configure-biteship.php
   - setup-biteship-database.php

2. **Configure webhook** (optional):
   - Go to Biteship Dashboard
   - Add webhook URL: `https://dorve.id/api/biteship/webhook.php`
   - Enable order tracking updates

3. **Test with real customers!**

---

Udah setup? Test langsung di checkout page!
**URL:** https://dorve.id/pages/checkout.php
