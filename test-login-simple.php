<?php
/**
 * Simple Login Test - No Redirect Loop
 */
require_once __DIR__ . '/config.php';

$result = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill all fields';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                $error = "❌ User not found: $email";
            } elseif (!password_verify($password, $user['password'])) {
                $error = "❌ Password does not match!<br><br>";
                $error .= "Debug Info:<br>";
                $error .= "- User found: ✅ {$user['name']}<br>";
                $error .= "- Email: {$user['email']}<br>";
                $error .= "- Role: {$user['role']}<br>";
                $error .= "- Password hash: " . substr($user['password'], 0, 30) . "...<br>";
                $error .= "<br><strong>Please reset password via reset-admin-password.php first!</strong>";
            } else {
                // Password correct! Set session
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['is_admin'] = ($user['role'] === 'admin') ? 1 : 0;

                $result = "<div style='background: #D1FAE5; padding: 20px; border-radius: 8px; margin-bottom: 20px;'>";
                $result .= "✅ <strong>LOGIN BERHASIL!</strong><br><br>";
                $result .= "<strong>User:</strong> {$user['name']}<br>";
                $result .= "<strong>Email:</strong> {$user['email']}<br>";
                $result .= "<strong>Role:</strong> {$user['role']}<br>";
                $result .= "<strong>Session ID:</strong> " . session_id() . "<br><br>";

                $result .= "<strong>Session Variables:</strong><br>";
                $result .= "- user_id: " . $_SESSION['user_id'] . "<br>";
                $result .= "- user_name: " . $_SESSION['user_name'] . "<br>";
                $result .= "- role: " . $_SESSION['role'] . "<br>";
                $result .= "- is_admin: " . $_SESSION['is_admin'] . "<br>";
                $result .= "</div>";

                $result .= "<div style='background: #FEF3C7; padding: 16px; border-radius: 6px; margin-bottom: 20px;'>";
                $result .= "⚠️ <strong>Sekarang test redirect:</strong><br>";
                $result .= "- isLoggedIn(): " . (isLoggedIn() ? '✅ TRUE' : '❌ FALSE') . "<br>";
                $result .= "- isAdmin(): " . (isAdmin() ? '✅ TRUE' : '❌ FALSE') . "<br>";
                $result .= "</div>";

                if ($user['role'] === 'admin') {
                    $result .= "<a href='/admin/index.php' style='display: inline-block; padding: 12px 24px; background: #1A1A1A; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; margin-right: 10px;'>→ Go to Admin Panel</a>";
                    $result .= "<a href='/admin/login.php' style='display: inline-block; padding: 12px 24px; background: #6B7280; color: white; text-decoration: none; border-radius: 6px; font-weight: 600;'>Test Admin Login Page</a>";
                } else {
                    $result .= "<a href='/member/dashboard.php' style='display: inline-block; padding: 12px 24px; background: #1A1A1A; color: white; text-decoration: none; border-radius: 6px; font-weight: 600;'>→ Go to Member Dashboard</a>";
                }
            }
        } catch (Exception $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Login Test - Dorve</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        h1 {
            text-align: center;
            margin-bottom: 10px;
            color: #1A1A1A;
        }

        .subtitle {
            text-align: center;
            color: #6B7280;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1A1A1A;
        }

        input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #E5E7EB;
            border-radius: 8px;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
        }

        button {
            width: 100%;
            padding: 14px;
            background: #1A1A1A;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        button:hover {
            background: #000;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .error {
            background: #FEE2E2;
            border: 2px solid #FCA5A5;
            color: #991B1B;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.6;
        }

        .info {
            background: #DBEAFE;
            border: 2px solid #93C5FD;
            color: #1E40AF;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            line-height: 1.6;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Simple Login Test</h1>
        <p class="subtitle">No redirect loop - Manual testing</p>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($result): ?>
            <?php echo $result; ?>
        <?php else: ?>
            <div class="info">
                <strong>📋 Test Instructions:</strong><br>
                1. Reset password via <a href="/reset-admin-password.php" target="_blank" style="color: #1E40AF; font-weight: 600;">reset-admin-password.php</a><br>
                2. Make sure you see "Password Test: ✅ Verified!"<br>
                3. Come back here and login with the same password<br>
                4. Check if session variables are set correctly<br>
                5. Click the link to test if redirect works
            </div>

            <form method="POST">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="admin1@dorve.id" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter password..." required>
                </div>

                <button type="submit">🚀 Test Login</button>
            </form>

            <div class="back-link">
                <a href="/diagnose-login.php">← Back to Diagnosis</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
