<?php
/**
 * Comprehensive Login Diagnosis Tool
 */

// Start output buffering to catch any output before session_start
ob_start();

echo "<pre>";
echo "=== COMPREHENSIVE LOGIN DIAGNOSIS ===\n\n";

// 1. Check PHP version
echo "1. PHP VERSION\n";
echo "   PHP Version: " . phpversion() . "\n";
echo "   Status: " . (version_compare(phpversion(), '7.4.0', '>=') ? '✅ OK' : '❌ TOO OLD') . "\n\n";

// 2. Check session configuration BEFORE starting session
echo "2. SESSION CONFIGURATION (Before session_start)\n";
echo "   session.auto_start: " . ini_get('session.auto_start') . "\n";
echo "   session.save_path: " . ini_get('session.save_path') . "\n";
echo "   session.save_handler: " . ini_get('session.save_handler') . "\n";
echo "   session.use_cookies: " . ini_get('session.use_cookies') . "\n";
echo "   session.cookie_httponly: " . ini_get('session.cookie_httponly') . "\n\n";

// 3. Try to start session
echo "3. SESSION START TEST\n";
try {
    if (session_status() === PHP_SESSION_NONE) {
        $result = session_start();
        echo "   session_start() result: " . ($result ? '✅ TRUE' : '❌ FALSE') . "\n";
        echo "   session_status(): " . session_status() . " (2 = PHP_SESSION_ACTIVE)\n";
        echo "   session_id(): " . session_id() . "\n";

        // Test writing to session
        $_SESSION['test'] = 'test_value_' . time();
        echo "   Test write to session: ✅ OK\n";
        echo "   Test value: " . $_SESSION['test'] . "\n";
    } else {
        echo "   Session already started\n";
        echo "   session_id(): " . session_id() . "\n";
    }
} catch (Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
}
echo "\n";

// 4. Check headers
echo "4. HEADERS CHECK\n";
if (headers_sent($file, $line)) {
    echo "   ❌ Headers already sent!\n";
    echo "   File: $file\n";
    echo "   Line: $line\n";
} else {
    echo "   ✅ Headers not sent yet\n";
}
echo "\n";

// 5. Now load config
echo "5. LOADING CONFIG.PHP\n";
try {
    require_once __DIR__ . '/config.php';
    echo "   ✅ config.php loaded\n";
    echo "   SITE_URL: " . SITE_URL . "\n";
    echo "   DB_HOST: " . DB_HOST . "\n";
} catch (Exception $e) {
    echo "   ❌ Error loading config: " . $e->getMessage() . "\n";
}
echo "\n";

// 6. Test database connection
echo "6. DATABASE CONNECTION\n";
try {
    if (isset($pdo)) {
        $pdo->query("SELECT 1");
        echo "   ✅ Database connected\n";
    } else {
        echo "   ❌ PDO not initialized\n";
    }
} catch (Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n";
}
echo "\n";

// 7. Get users from database
echo "7. USERS IN DATABASE\n";
try {
    $stmt = $pdo->query("SELECT id, name, email, role, email_verified FROM users ORDER BY role, id LIMIT 10");
    $users = $stmt->fetchAll();

    foreach ($users as $user) {
        $verified = $user['email_verified'] ? '✅' : '❌';
        echo "   [{$user['role']}] {$user['name']} - {$user['email']} - Verified: $verified\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// 8. Test login simulation
echo "8. LOGIN SIMULATION TEST\n";
$test_email = 'admin1@dorve.id';
$test_passwords = ['password123', 'admin123', 'Qwerty88!', '123456'];

try {
    $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
    $stmt->execute([$test_email]);
    $user = $stmt->fetch();

    if ($user) {
        echo "   User found: {$user['email']}\n";
        echo "   User ID: {$user['id']}\n";
        echo "   User Role: {$user['role']}\n";
        echo "   Password hash: " . substr($user['password'], 0, 30) . "...\n\n";

        echo "   Testing common passwords:\n";
        foreach ($test_passwords as $pwd) {
            $match = password_verify($pwd, $user['password']);
            echo "   - '$pwd': " . ($match ? '✅ MATCH!' : '❌ No match') . "\n";
        }

        // Test actual login process
        echo "\n   Simulating login process:\n";
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['is_admin'] = ($user['role'] === 'admin') ? 1 : 0;

        echo "   Set session variables:\n";
        echo "   - user_id: " . $_SESSION['user_id'] . "\n";
        echo "   - user_name: " . $_SESSION['user_name'] . "\n";
        echo "   - role: " . $_SESSION['role'] . "\n";
        echo "   - is_admin: " . $_SESSION['is_admin'] . "\n";

        // Check if session persists
        if (isset($_SESSION['user_id'])) {
            echo "   ✅ Session variables set successfully!\n";
        } else {
            echo "   ❌ Session variables not persisting!\n";
        }

    } else {
        echo "   ❌ User not found\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// 9. Test helper functions
echo "9. HELPER FUNCTIONS TEST\n";
try {
    echo "   isLoggedIn(): " . (isLoggedIn() ? '✅ TRUE' : '❌ FALSE') . "\n";
    echo "   isAdmin(): " . (isAdmin() ? '✅ TRUE' : '❌ FALSE') . "\n";
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// 10. Session contents
echo "10. SESSION CONTENTS\n";
if (isset($_SESSION) && !empty($_SESSION)) {
    echo "   Session data:\n";
    foreach ($_SESSION as $key => $value) {
        if (is_scalar($value)) {
            echo "   - $key: $value\n";
        } else {
            echo "   - $key: " . gettype($value) . "\n";
        }
    }
} else {
    echo "   ❌ Session is empty\n";
}
echo "\n";

// 11. File permissions check
echo "11. FILE PERMISSIONS\n";
$files_to_check = [
    'config.php',
    'auth/login.php',
    'admin/login.php',
    'includes/email-helper.php'
];

foreach ($files_to_check as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        echo "   $file: $perms " . (is_readable($path) ? '✅' : '❌') . "\n";
    } else {
        echo "   $file: ❌ NOT FOUND\n";
    }
}
echo "\n";

// 12. Server environment
echo "12. SERVER ENVIRONMENT\n";
echo "   SERVER_SOFTWARE: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";
echo "   DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo "   HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'N/A') . "\n";
echo "   REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
echo "   HTTPS: " . ($_SERVER['HTTPS'] ?? 'not set') . "\n";
echo "\n";

// 13. Cookie test
echo "13. COOKIE TEST\n";
if (headers_sent()) {
    echo "   ⚠️  Cannot set test cookie - headers already sent\n";
} else {
    setcookie('test_cookie', 'test_value_' . time(), time() + 3600, '/');
    echo "   ✅ Test cookie set\n";
}
if (isset($_COOKIE) && !empty($_COOKIE)) {
    echo "   Cookies received:\n";
    foreach ($_COOKIE as $name => $value) {
        echo "   - $name: " . substr($value, 0, 50) . "\n";
    }
} else {
    echo "   ⚠️  No cookies received\n";
}
echo "\n";

echo "=== END OF DIAGNOSIS ===\n";
echo "</pre>";

// Show any buffered output at the end
ob_end_flush();
