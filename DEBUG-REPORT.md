# Debug Report - Login & Email Issues

## 🔍 Masalah Yang Ditemukan

### 1. **Login Member Page & Admin Page - Loading tanpa Action**

**Penyebab:**
- Error message di login page menggunakan `htmlspecialchars()` pada HTML content
- Ketika ada HTML tags di error message (seperti `<strong>`, `<br>`), mereka di-escape dan tidak ditampilkan dengan benar
- Ini menyebabkan halaman tidak render dengan sempurna

**File yang bermasalah:**
- `/auth/login.php` - Line 184
- `/auth/forgot-password.php` - Line 199-206

**Solusi yang dilakukan:**
- ✅ Remove `htmlspecialchars()` dari error display di login pages
- ✅ Error message sekarang bisa tampil dengan formatting HTML yang benar

### 2. **Email Helper Tidak Bekerja (Forgot Password)**

**Penyebab:**
- Email helper (`includes/email-helper.php`) mencoba menggunakan PHPMailer
- PHPMailer library tidak terinstall (tidak ada vendor/autoload.php)
- Ketika PHPMailer tidak ada, USE_SMTP tetap false tapi masih ada reference ke PHPMailer classes

**File yang bermasalah:**
- `/includes/email-helper.php`

**Solusi yang dilakukan:**
- ✅ Disable PHPMailer requirement
- ✅ Update email helper untuk langsung menggunakan PHP mail() function
- ✅ Email sekarang akan menggunakan server's mail configuration

### 3. **Lupa Password Admin/Member**

**Masalah:**
- User mungkin lupa password dan tidak bisa login
- Tidak ada cara mudah untuk reset password admin

**Solusi yang dilakukan:**
- ✅ Buat utility script `reset-admin-password.php` untuk reset password
- ✅ Buat halaman `resend-verification.php` untuk kirim ulang email verifikasi

---

## ✅ Files Yang Sudah Di-Fix

1. **auth/login.php** - Fixed error message display
2. **auth/forgot-password.php** - Fixed error/success message display
3. **includes/email-helper.php** - Simplified to use PHP mail() directly
4. **auth/resend-verification.php** - NEW: Kirim ulang email verifikasi
5. **reset-admin-password.php** - NEW: Utility untuk reset password admin

---

## 🚀 Cara Test Login

### Test Admin Login:

1. Buka: `https://dorve.id/reset-admin-password.php`
2. Pilih email admin yang mau direset (lihat daftar admin)
3. Masukkan password baru (minimal 6 karakter)
4. Submit form
5. Login di `/admin/login.php` dengan password baru

### Test Member Login:

1. Cek database untuk user dengan `role = 'customer'`
2. Jika lupa password, gunakan "Lupa Password?" di halaman login
3. Atau gunakan `reset-admin-password.php` untuk reset password member juga

### Email yang Terdaftar (dari database):

**Admin Accounts:**
- admin1@dorve.id
- admin2@dorve.id
- admin1@dorve.co
- admin2@dorve.co

**Customer Accounts:**
- officialangkalytica@gmail.com
- dorveofficial@gmail.com
- skywii8089@gmail.com (email verified)

---

## ⚙️ Konfigurasi Email

Email saat ini menggunakan PHP `mail()` function. Untuk testing production:

### Option 1: Gunakan PHP mail() (Current)
Server harus sudah dikonfigurasi dengan sendmail/postfix.

### Option 2: Install PHPMailer (Recommended for Production)

Jika ingin menggunakan SMTP Gmail yang sudah dikonfigurasi:

```bash
# Install composer jika belum ada
curl -sS https://getcomposer.org/installer | php

# Install PHPMailer
php composer.phar install
```

Email configuration sudah ada di `includes/email-helper.php`:
- SMTP Host: smtp.gmail.com
- Username: dorveofficial@gmail.com
- Password: App Password sudah terkonfigurasi

---

## 🔐 Security Notes

1. **HAPUS FILE INI SETELAH TESTING:**
   - `reset-admin-password.php` - Utility untuk reset password
   - `DEBUG-REPORT.md` - File ini
   - `test-db.php` - Test database connection

2. **Email Verification:**
   - Member dengan `email_verified = 0` tidak bisa login
   - Admin bisa login tanpa verifikasi email
   - Gunakan `resend-verification.php` untuk kirim ulang email verifikasi

3. **Password Requirements:**
   - Minimal 6 karakter (bisa diubah di validation)
   - Password di-hash menggunakan PHP `password_hash()` dengan bcrypt

---

## 📝 Testing Checklist

- [ ] Admin login works dengan password baru
- [ ] Member login works
- [ ] Forgot password mengirim email (cek email/spam folder)
- [ ] Resend verification email works
- [ ] Error messages tampil dengan benar
- [ ] Session berfungsi setelah login (tidak logout otomatis)

---

## 🆘 Troubleshooting

### Login masih tidak berfungsi:

1. **Check browser console** untuk JavaScript errors
2. **Check browser network tab** untuk melihat HTTP response
3. **Check server error logs** untuk PHP errors
4. **Check database connection** - pastikan config.php benar

### Email tidak terkirim:

1. **Check spam folder** - Email mungkin masuk spam
2. **Check server mail logs** - `/var/log/mail.log`
3. **Test PHP mail()** function dengan script sederhana
4. **Consider using SMTP** dengan PHPMailer untuk reliability

### Database Issues:

```sql
-- Check users in database
SELECT id, name, email, role, email_verified FROM users;

-- Reset password manually
UPDATE users
SET password = '$2y$10$YourHashedPasswordHere'
WHERE email = 'admin1@dorve.id';
```

---

## 📞 Support

Jika masih ada masalah:

1. Screenshoot error message
2. Check browser console (F12)
3. Check network tab di browser
4. Share error logs dari server

---

**Generated:** <?php echo date('Y-m-d H:i:s'); ?>

**Status:** All fixes applied ✅
