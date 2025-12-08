<?php
// DEBUG ADMIN LOGIN - Complete Flow Test
require_once __DIR__ . '/config.php';

$debugMode = true;
$results = [];

// Test 1: Session Configuration
$results['session'] = [
    'status' => session_status(),
    'status_text' => session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE',
    'session_id' => session_id(),
    'session_data' => $_SESSION ?? [],
    'cookie_params' => session_get_cookie_params()
];

// Test 2: Check if already logged in
$results['auth_check'] = [
    'isLoggedIn' => isLoggedIn(),
    'isAdmin' => isAdmin(),
    'user_id' => $_SESSION['user_id'] ?? 'not set',
    'role' => $_SESSION['role'] ?? 'not set',
    'is_admin' => $_SESSION['is_admin'] ?? 'not set'
];

// Test 3: Process Login (if POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $results['post_data'] = [
        'email' => $email,
        'password_length' => strlen($password),
        'method' => $_SERVER['REQUEST_METHOD']
    ];

    if ($email && $password) {
        try {
            // Query user
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            $results['database'] = [
                'query_success' => true,
                'user_found' => $user ? true : false,
                'user_data' => $user ? [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'password_hash' => substr($user['password'], 0, 20) . '...'
                ] : null
            ];

            if ($user) {
                // Test password verify
                $passwordMatch = password_verify($password, $user['password']);
                $results['password_verify'] = [
                    'match' => $passwordMatch,
                    'input_password' => $password,
                    'hash_algorithm' => password_get_info($user['password'])
                ];

                if ($passwordMatch) {
                    // Try to set session
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['role'] = 'admin';
                    $_SESSION['is_admin'] = 1;

                    $results['session_set'] = [
                        'success' => true,
                        'user_id' => $_SESSION['user_id'],
                        'user_name' => $_SESSION['user_name'],
                        'role' => $_SESSION['role'],
                        'is_admin' => $_SESSION['is_admin']
                    ];

                    // Test auth functions after setting session
                    $results['auth_recheck'] = [
                        'isLoggedIn' => isLoggedIn(),
                        'isAdmin' => isAdmin()
                    ];

                    // Test headers
                    $results['headers'] = [
                        'headers_sent' => headers_sent($file, $line),
                        'headers_sent_file' => $file ?? 'none',
                        'headers_sent_line' => $line ?? 'none',
                        'can_redirect' => !headers_sent()
                    ];

                    $results['login_result'] = 'SUCCESS - All checks passed!';
                } else {
                    $results['login_result'] = 'FAILED - Password does not match';
                }
            } else {
                $results['login_result'] = 'FAILED - User not found or not admin';
            }
        } catch (Exception $e) {
            $results['database'] = [
                'query_success' => false,
                'error' => $e->getMessage()
            ];
            $results['login_result'] = 'ERROR - Database exception';
        }
    } else {
        $results['login_result'] = 'ERROR - Email or password empty';
    }
}

// Test 4: PHP Configuration
$results['php_config'] = [
    'version' => PHP_VERSION,
    'session.cookie_httponly' => ini_get('session.cookie_httponly'),
    'session.cookie_secure' => ini_get('session.cookie_secure'),
    'session.cookie_samesite' => ini_get('session.cookie_samesite'),
    'output_buffering' => ini_get('output_buffering')
];

// Test 5: Server Variables
$results['server'] = [
    'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
    'REQUEST_URI' => $_SERVER['REQUEST_URI'],
    'HTTP_HOST' => $_SERVER['HTTP_HOST'],
    'HTTPS' => $_SERVER['HTTPS'] ?? 'not set',
    'SERVER_SOFTWARE' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 Debug Admin Login Flow</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Consolas', 'Monaco', monospace;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 28px;
        }
        h2 {
            color: #333;
            margin-top: 30px;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #667eea;
            font-size: 20px;
        }
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 12px;
            margin-left: 10px;
        }
        .status.success { background: #D1FAE5; color: #065F46; }
        .status.error { background: #FEE; color: #C33; }
        .status.warning { background: #FEF3C7; color: #92400E; }
        .status.info { background: #DBEAFE; color: #1E40AF; }
        .result-box {
            background: #F9FAFB;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }
        .result-box pre {
            margin: 0;
            font-size: 13px;
            color: #374151;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .login-form {
            background: #F3F4F6;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #374151;
        }
        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #D1D5DB;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
        }
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
        }
        button:hover {
            background: #5568d3;
        }
        .action-links {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #E5E7EB;
        }
        .action-links a {
            display: inline-block;
            margin-right: 15px;
            padding: 8px 16px;
            background: #374151;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 13px;
        }
        .action-links a:hover {
            background: #1F2937;
        }
        .highlight {
            background: #FEF3C7;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Debug Admin Login Flow</h1>
        <p style="color: #6B7280; margin-bottom: 20px;">Complete diagnostic tool to identify admin login issues</p>

        <?php if (isset($results['login_result'])): ?>
            <div class="result-box" style="border-left: 4px solid <?php
                echo strpos($results['login_result'], 'SUCCESS') !== false ? '#10B981' : '#EF4444';
            ?>;">
                <h3 style="margin: 0 0 10px 0; color: <?php
                    echo strpos($results['login_result'], 'SUCCESS') !== false ? '#065F46' : '#991B1B';
                ?>;">
                    <?php echo $results['login_result']; ?>
                </h3>
                <?php if (strpos($results['login_result'], 'SUCCESS') !== false): ?>
                    <p style="margin: 0; color: #374151;">
                        ✅ Login credentials are valid and session has been set!<br>
                        Now testing redirect capability...
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <h2>🔐 Test Login Form</h2>
        <div class="login-form">
            <form method="POST">
                <div class="form-group">
                    <label for="email">Admin Email:</label>
                    <input type="email" id="email" name="email" value="admin1@dorve.id" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" placeholder="Enter admin password" required>
                </div>
                <button type="submit">🧪 Test Login Flow</button>
            </form>
        </div>

        <!-- Session Status -->
        <h2>📦 Session Status
            <span class="status <?php echo $results['session']['status'] === PHP_SESSION_ACTIVE ? 'success' : 'error'; ?>">
                <?php echo $results['session']['status_text']; ?>
            </span>
        </h2>
        <div class="result-box">
            <pre><?php print_r($results['session']); ?></pre>
        </div>

        <!-- Auth Check -->
        <h2>🔑 Authentication Status
            <?php if ($results['auth_check']['isLoggedIn'] && $results['auth_check']['isAdmin']): ?>
                <span class="status success">LOGGED IN AS ADMIN</span>
            <?php elseif ($results['auth_check']['isLoggedIn']): ?>
                <span class="status warning">LOGGED IN (NOT ADMIN)</span>
            <?php else: ?>
                <span class="status error">NOT LOGGED IN</span>
            <?php endif; ?>
        </h2>
        <div class="result-box">
            <pre><?php print_r($results['auth_check']); ?></pre>
        </div>

        <?php if (isset($results['post_data'])): ?>
            <!-- POST Data -->
            <h2>📨 POST Data Received</h2>
            <div class="result-box">
                <pre><?php print_r($results['post_data']); ?></pre>
            </div>
        <?php endif; ?>

        <?php if (isset($results['database'])): ?>
            <!-- Database Query -->
            <h2>💾 Database Query
                <span class="status <?php echo $results['database']['query_success'] ? 'success' : 'error'; ?>">
                    <?php echo $results['database']['query_success'] ? 'SUCCESS' : 'FAILED'; ?>
                </span>
            </h2>
            <div class="result-box">
                <pre><?php print_r($results['database']); ?></pre>
            </div>
        <?php endif; ?>

        <?php if (isset($results['password_verify'])): ?>
            <!-- Password Verification -->
            <h2>🔐 Password Verification
                <span class="status <?php echo $results['password_verify']['match'] ? 'success' : 'error'; ?>">
                    <?php echo $results['password_verify']['match'] ? 'MATCH' : 'NO MATCH'; ?>
                </span>
            </h2>
            <div class="result-box">
                <pre><?php
                    $pv = $results['password_verify'];
                    unset($pv['input_password']); // Don't show password in output
                    print_r($pv);
                ?></pre>
            </div>
        <?php endif; ?>

        <?php if (isset($results['session_set'])): ?>
            <!-- Session Set -->
            <h2>✅ Session Variables Set</h2>
            <div class="result-box">
                <pre><?php print_r($results['session_set']); ?></pre>
            </div>
        <?php endif; ?>

        <?php if (isset($results['auth_recheck'])): ?>
            <!-- Auth Recheck -->
            <h2>🔄 Auth Functions After Login
                <?php if ($results['auth_recheck']['isLoggedIn'] && $results['auth_recheck']['isAdmin']): ?>
                    <span class="status success">BOTH TRUE ✅</span>
                <?php else: ?>
                    <span class="status error">FAILED ❌</span>
                <?php endif; ?>
            </h2>
            <div class="result-box">
                <pre><?php print_r($results['auth_recheck']); ?></pre>
                <?php if (!$results['auth_recheck']['isLoggedIn'] || !$results['auth_recheck']['isAdmin']): ?>
                    <p style="color: #991B1B; margin-top: 10px; font-weight: bold;">
                        ⚠️ PROBLEM DETECTED: isLoggedIn() or isAdmin() returns FALSE even after setting session!<br>
                        This means there's an issue with the auth helper functions in config.php
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($results['headers'])): ?>
            <!-- Headers Check -->
            <h2>📤 Headers & Redirect Capability
                <span class="status <?php echo $results['headers']['can_redirect'] ? 'success' : 'error'; ?>">
                    <?php echo $results['headers']['can_redirect'] ? 'CAN REDIRECT' : 'CANNOT REDIRECT'; ?>
                </span>
            </h2>
            <div class="result-box">
                <pre><?php print_r($results['headers']); ?></pre>
                <?php if (!$results['headers']['can_redirect']): ?>
                    <p style="color: #991B1B; margin-top: 10px; font-weight: bold;">
                        ⚠️ PROBLEM DETECTED: Headers already sent!<br>
                        File: <span class="highlight"><?php echo $results['headers']['headers_sent_file']; ?></span><br>
                        Line: <span class="highlight"><?php echo $results['headers']['headers_sent_line']; ?></span><br>
                        <br>
                        This prevents PHP header() redirects from working. Check for whitespace or output before &lt;?php tag.
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- PHP Config -->
        <h2>⚙️ PHP Configuration</h2>
        <div class="result-box">
            <pre><?php print_r($results['php_config']); ?></pre>
        </div>

        <!-- Server Info -->
        <h2>🌐 Server Information</h2>
        <div class="result-box">
            <pre><?php print_r($results['server']); ?></pre>
        </div>

        <!-- Action Links -->
        <div class="action-links">
            <a href="/admin/login.php">→ Go to Admin Login</a>
            <a href="/admin/index.php">→ Try Admin Panel</a>
            <a href="/test-login-simple.php">→ Simple Login Test</a>
            <a href="?clear=1" onclick="sessionStorage.clear(); return confirm('Clear session and reload?');">🗑️ Clear Session</a>
        </div>

        <?php if (isset($_GET['clear'])): ?>
            <?php
            session_destroy();
            echo '<script>alert("Session cleared!"); window.location.href="debug-admin-login.php";</script>';
            ?>
        <?php endif; ?>
    </div>

    <script>
        console.log('Debug Admin Login loaded');
        console.log('Results:', <?php echo json_encode($results, JSON_PRETTY_PRINT); ?>);
    </script>
</body>
</html>
