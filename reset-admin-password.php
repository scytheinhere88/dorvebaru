<?php
/**
 * Utility Script: Reset Admin Password
 *
 * This script allows you to reset admin password if you forget it.
 * Access via browser: https://dorve.id/reset-admin-password.php
 *
 * SECURITY: Delete this file after use!
 */

require_once __DIR__ . '/config.php';

$message = '';
$error = '';

// Process password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (empty($email) || empty($new_password) || empty($confirm_password)) {
        $error = 'Semua field harus diisi!';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Password dan konfirmasi password tidak sama!';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } else {
        try {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id, name, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                $error = 'Email tidak ditemukan!';
            } else {
                // Hash new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // Update password
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user['id']]);

                $message = "✅ Password untuk <strong>{$user['name']}</strong> ({$email}) berhasil direset!<br>";
                $message .= "Role: {$user['role']}<br><br>";
                $message .= "<strong>PENTING:</strong> Hapus file reset-admin-password.php setelah selesai!<br>";
                $message .= "<a href='/auth/login.php' style='color: #1A1A1A; font-weight: 600;'>→ Login Sekarang</a>";
            }
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get all admin users
$admins = [];
try {
    $stmt = $pdo->query("SELECT id, name, email, role FROM users WHERE role = 'admin' ORDER BY id");
    $admins = $stmt->fetchAll();
} catch (Exception $e) {
    $error = 'Error fetching admins: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Admin Password - Dorve</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1A1A1A 0%, #2D2D2D 100%);
            padding: 40px 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        h1 {
            font-size: 28px;
            margin-bottom: 8px;
            color: #1A1A1A;
        }

        .subtitle {
            color: #6B7280;
            margin-bottom: 32px;
            font-size: 14px;
        }

        .warning {
            background: #FEF3C7;
            border-left: 4px solid #F59E0B;
            padding: 16px;
            border-radius: 6px;
            margin-bottom: 24px;
            font-size: 14px;
            color: #92400E;
        }

        .admin-list {
            background: #F3F4F6;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 32px;
        }

        .admin-list h3 {
            font-size: 16px;
            margin-bottom: 12px;
            color: #1A1A1A;
        }

        .admin-item {
            background: white;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-info {
            font-size: 14px;
        }

        .admin-name {
            font-weight: 600;
            color: #1A1A1A;
        }

        .admin-email {
            color: #6B7280;
            font-size: 13px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 14px;
            color: #1A1A1A;
        }

        input, select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #E5E7EB;
            border-radius: 6px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #1A1A1A;
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: #1A1A1A;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn:hover {
            background: #000;
            transform: translateY(-1px);
        }

        .success {
            background: #D1FAE5;
            border-left: 4px solid #10B981;
            padding: 16px;
            border-radius: 6px;
            margin-bottom: 24px;
            font-size: 14px;
            color: #065F46;
        }

        .error {
            background: #FEE2E2;
            border-left: 4px solid #EF4444;
            padding: 16px;
            border-radius: 6px;
            margin-bottom: 24px;
            font-size: 14px;
            color: #991B1B;
        }

        .links {
            text-align: center;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #E5E7EB;
        }

        .links a {
            color: #1A1A1A;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            margin: 0 12px;
        }

        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Reset Admin Password</h1>
        <p class="subtitle">Utility untuk reset password admin yang lupa password</p>

        <div class="warning">
            <strong>⚠️ SECURITY WARNING:</strong><br>
            File ini dapat digunakan untuk mereset password admin. Hapus file <code>reset-admin-password.php</code> setelah selesai digunakan!
        </div>

        <?php if ($admins): ?>
        <div class="admin-list">
            <h3>📋 Daftar Admin</h3>
            <?php foreach ($admins as $admin): ?>
            <div class="admin-item">
                <div class="admin-info">
                    <div class="admin-name"><?php echo htmlspecialchars($admin['name']); ?></div>
                    <div class="admin-email"><?php echo htmlspecialchars($admin['email']); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($message): ?>
        <div class="success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="error">❌ <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email Admin</label>
                <input type="email" id="email" name="email" required placeholder="admin@dorve.id">
            </div>

            <div class="form-group">
                <label for="new_password">Password Baru</label>
                <input type="password" id="new_password" name="new_password" required placeholder="Minimal 6 karakter">
            </div>

            <div class="form-group">
                <label for="confirm_password">Konfirmasi Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required placeholder="Ketik ulang password">
            </div>

            <button type="submit" class="btn">🔐 Reset Password</button>
        </form>

        <div class="links">
            <a href="/auth/login.php">Member Login</a>
            <a href="/admin/login.php">Admin Login</a>
            <a href="/index.php">← Back to Home</a>
        </div>
    </div>
</body>
</html>
