<?php
require_once __DIR__ . '/../config.php';

if (isLoggedIn()) {
    redirect('/member/dashboard.php');
}

$error = '';
$success = '';
$email = trim($_GET['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Mohon masukkan email Anda';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid';
    } else {
        $stmt = $pdo->prepare("SELECT id, name, email, email_verified FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            if ($user['email_verified']) {
                $error = 'Email Anda sudah diverifikasi. Silakan <a href="/auth/login.php">login</a>';
            } else {
                // Generate new verification token
                $verification_token = bin2hex(random_bytes(32));
                $verification_expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

                $stmt = $pdo->prepare("
                    UPDATE users
                    SET email_verification_token = ?,
                        email_verification_expiry = ?,
                        verification_attempts = COALESCE(verification_attempts, 0) + 1,
                        last_verification_sent = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$verification_token, $verification_expiry, $user['id']]);

                // Send verification email
                try {
                    require_once __DIR__ . '/../includes/email-helper.php';
                    $verification_link = SITE_URL . "auth/verify-email.php?token=" . $verification_token;
                    $emailSent = sendVerificationEmail($user['email'], $user['name'], $verification_link);

                    if ($emailSent) {
                        $success = 'Email verifikasi telah dikirim ulang! Silakan cek inbox atau folder spam Anda.';
                    } else {
                        $error = 'Gagal mengirim email. Silakan coba lagi nanti atau hubungi admin.';
                    }
                } catch (Exception $e) {
                    error_log('Verification email error: ' . $e->getMessage());
                    $error = 'Terjadi kesalahan. Silakan coba lagi.';
                }
            }
        } else {
            $success = 'Jika email terdaftar, link verifikasi akan dikirim.';
        }
    }
}

$page_title = 'Kirim Ulang Email Verifikasi - Dorve House';
$page_description = 'Kirim ulang email verifikasi akun Dorve House';
include __DIR__ . '/../includes/header.php';
?>

<style>
    .auth-container {
        max-width: 480px;
        margin: 80px auto;
        padding: 0 24px;
    }

    .auth-card {
        background: var(--white);
        padding: 60px 50px;
        border: 1px solid rgba(0,0,0,0.08);
        border-radius: 8px;
    }

    .auth-title {
        font-family: 'Playfair Display', serif;
        font-size: 28px;
        margin-bottom: 12px;
        text-align: center;
    }

    .auth-subtitle {
        text-align: center;
        color: var(--grey);
        font-size: 14px;
        margin-bottom: 40px;
        line-height: 1.6;
    }

    .form-group {
        margin-bottom: 24px;
    }

    .form-label {
        display: block;
        font-size: 13px;
        font-weight: 500;
        margin-bottom: 8px;
        color: var(--charcoal);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-input {
        width: 100%;
        padding: 14px 16px;
        border: 1px solid rgba(0,0,0,0.15);
        border-radius: 4px;
        font-size: 14px;
        transition: all 0.3s;
    }

    .form-input:focus {
        outline: none;
        border-color: var(--charcoal);
    }

    .submit-btn {
        width: 100%;
        padding: 16px;
        background: var(--charcoal);
        color: var(--white);
        border: none;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        text-transform: uppercase;
        letter-spacing: 1px;
        transition: all 0.3s;
    }

    .submit-btn:hover {
        background: #000;
    }

    .alert {
        padding: 16px;
        border-radius: 6px;
        margin-bottom: 24px;
        font-size: 14px;
    }

    .alert-error {
        background: #FEE2E2;
        color: #991B1B;
        border: 1px solid #FCA5A5;
    }

    .alert-success {
        background: #D1FAE5;
        color: #065F46;
        border: 1px solid #6EE7B7;
    }

    .back-link {
        text-align: center;
        margin-top: 24px;
        font-size: 14px;
    }

    .back-link a {
        color: var(--charcoal);
        text-decoration: underline;
    }

    .icon-wrapper {
        text-align: center;
        font-size: 48px;
        margin-bottom: 20px;
    }
</style>

<div class="auth-container">
    <div class="auth-card">
        <div class="icon-wrapper">📧</div>
        <h1 class="auth-title">Kirim Ulang Email Verifikasi</h1>
        <p class="auth-subtitle">
            Belum menerima email verifikasi? Masukkan email Anda dan kami akan mengirim ulang link verifikasinya.
        </p>

        <?php if ($error): ?>
            <div class="alert alert-error">
                ❌ <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                ✅ <?php echo $success; ?>
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input
                        type="email"
                        name="email"
                        class="form-input"
                        placeholder="nama@email.com"
                        required
                        value="<?php echo htmlspecialchars($email); ?>"
                    >
                </div>

                <button type="submit" class="submit-btn">
                    📧 Kirim Ulang Email Verifikasi
                </button>
            </form>
        <?php endif; ?>

        <div class="back-link">
            <a href="/auth/login.php">← Kembali ke Login</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
