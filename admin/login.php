<?php
require_once __DIR__ . '/../config.php';

if (isLoggedIn() && isAdmin()) {
    redirect('/admin/index.php');
}

$error = '';
$debug_info = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Start output buffering to prevent "headers already sent" issue
    ob_start();

    if ($email && $password) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Regenerate session ID for security
                session_regenerate_id(true);

                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['role'] = 'admin';
                $_SESSION['is_admin'] = 1;

                // Clear any output buffer
                ob_end_clean();

                // Force redirect with multiple methods
                if (!headers_sent()) {
                    header("Location: /admin/index.php");
                    exit();
                } else {
                    echo '<script>window.location.href="/admin/index.php";</script>';
                    echo '<noscript><meta http-equiv="refresh" content="0;url=/admin/index.php"></noscript>';
                    exit();
                }
            } else {
                ob_end_clean();
                $error = 'Invalid email or password';
                $debug_info = "Login attempt for: $email - " . ($user ? "User found but password mismatch" : "User not found");
                error_log("Failed admin login attempt for: $email");
            }
        } catch (Exception $e) {
            ob_end_clean();
            $error = 'System error. Please try again.';
            $debug_info = "Exception: " . $e->getMessage();
            error_log("Admin login error: " . $e->getMessage());
        }
    } else {
        ob_end_clean();
        $error = 'Please fill all fields';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Dorve</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1A1A1A 0%, #2D2D2D 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 12px;
            padding: 48px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .login-logo {
            text-align: center;
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 3px;
            margin-bottom: 12px;
            color: #1A1A1A;
        }

        .login-subtitle {
            text-align: center;
            color: #6F6F6F;
            margin-bottom: 40px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 14px;
            color: #1A1A1A;
        }

        input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #E8E8E8;
            border-radius: 6px;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s;
        }

        input:focus {
            outline: none;
            border-color: #1A1A1A;
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            background: #1A1A1A;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 12px;
        }

        .btn-login:hover {
            background: #000000;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .error {
            background: #FEE;
            border: 1px solid #FCC;
            color: #C33;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 24px;
            font-size: 14px;
        }

        .back-link {
            text-align: center;
            margin-top: 24px;
        }

        .back-link a {
            color: #6F6F6F;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }

        .back-link a:hover {
            color: #1A1A1A;
        }

        .demo-info {
            background: #F8F9FA;
            padding: 16px;
            border-radius: 6px;
            margin-bottom: 24px;
            font-size: 13px;
            color: #6F6F6F;
        }

        .demo-info strong {
            color: #1A1A1A;
            display: block;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">DORVE</div>
        <div class="login-subtitle">Admin Panel Login</div>

        <?php if ($error): ?>
            <div class="error">
                <?php echo htmlspecialchars($error); ?>
                <?php if ($debug_info): ?>
                    <br><small style="opacity: 0.7;"><?php echo htmlspecialchars($debug_info); ?></small>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error): ?>
            <div style="background: #D1FAE5; padding: 16px; border-radius: 6px; margin-bottom: 20px; color: #065F46;">
                <strong>Login successful! Redirecting...</strong><br>
                <small>If not redirected automatically, <a href="/admin/index.php" style="color: #065F46; font-weight: 600;">click here</a></small>
            </div>
        <?php endif; ?>

        <form method="POST" id="loginForm">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn-login">Login to Admin</button>
        </form>

        <div class="back-link">
            <a href="/index.php">← Back to Website</a>
        </div>
    </div>

    <script>
        // Debug logging
        console.log('Admin login page loaded');
        console.log('Form element:', document.getElementById('loginForm'));

        // Add submit event listener for debugging
        const form = document.getElementById('loginForm');
        const submitBtn = form.querySelector('button[type="submit"]');

        form.addEventListener('submit', function(e) {
            console.log('Form submitted!');
            console.log('Form method:', form.method);
            console.log('Form action:', form.action || 'current page');

            // Disable submit button to prevent double submit
            submitBtn.disabled = true;
            submitBtn.textContent = 'Logging in...';
            submitBtn.style.opacity = '0.7';

            // Re-enable after 5 seconds if still on page
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Login to Admin';
                submitBtn.style.opacity = '1';
                console.error('Form submission timeout - page did not redirect');
            }, 5000);
        });

        // Check if session storage has redirect loop flag
        if (sessionStorage.getItem('admin_login_attempt')) {
            const attempts = parseInt(sessionStorage.getItem('admin_login_attempt'));
            console.warn('Login attempt count:', attempts);

            if (attempts > 3) {
                alert('Multiple login attempts detected. Please clear browser cache and try again.\n\nChrome: Ctrl+Shift+Delete\nSelect "Cookies and other site data"\nTime range: "All time"\nClick "Clear data"');
                sessionStorage.removeItem('admin_login_attempt');
            } else {
                sessionStorage.setItem('admin_login_attempt', attempts + 1);
            }
        } else {
            sessionStorage.setItem('admin_login_attempt', '1');
        }
    </script>
</body>
</html>
