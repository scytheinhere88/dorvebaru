<?php
// VERIFY SESSION FIX - Test if session persists across page loads
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🧪 SESSION PERSISTENCE TEST</h1>";
echo "<style>body{font-family:monospace;padding:20px;background:#f9f9f9;} .success{color:green;font-weight:bold;} .error{color:red;font-weight:bold;} .info{color:blue;} pre{background:#fff;padding:15px;border:1px solid #ddd;border-radius:5px;} .box{background:#fff;padding:20px;margin:20px 0;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);} .btn{display:inline-block;padding:12px 24px;background:#4CAF50;color:white;text-decoration:none;border-radius:5px;margin:10px 5px;font-weight:bold;} .btn:hover{background:#45a049;}</style>";

require_once __DIR__ . '/config.php';

echo "<div class='box'>";
echo "<h2>✅ Step 1: Session Configuration Check</h2>";
echo "<pre>";
echo "Session Save Path: " . session_save_path() . "\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? "<span class='success'>ACTIVE ✅</span>" : "<span class='error'>INACTIVE ❌</span>") . "\n";
echo "Session Name: " . session_name() . "\n";
echo "</pre>";
echo "</div>";

// Check if custom session directory is being used
$custom_session_path = __DIR__ . '/sessions';
echo "<div class='box'>";
echo "<h2>📁 Step 2: Custom Session Directory Check</h2>";
echo "<pre>";
echo "Custom Path: " . $custom_session_path . "\n";
echo "Is Being Used: " . (session_save_path() === $custom_session_path ? "<span class='success'>YES ✅</span>" : "<span class='error'>NO ❌</span>") . "\n";

if (file_exists($custom_session_path)) {
    echo "Directory Exists: <span class='success'>YES ✅</span>\n";
    echo "Is Writable: " . (is_writable($custom_session_path) ? "<span class='success'>YES ✅</span>" : "<span class='error'>NO ❌</span>") . "\n";

    $files = glob($custom_session_path . '/sess_*');
    echo "Session Files Count: " . count($files) . "\n";

    if (count($files) > 0) {
        echo "\nLatest 3 session files:\n";
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        foreach (array_slice($files, 0, 3) as $file) {
            $age = time() - filemtime($file);
            echo "  • " . basename($file) . " - " . date('H:i:s', filemtime($file)) . " (" . $age . "s ago)\n";
        }
    }
} else {
    echo "Directory Exists: <span class='error'>NO ❌</span>\n";
    echo "<span class='error'>⚠️ Custom session directory not found! Fix may not be applied!</span>\n";
}
echo "</pre>";
echo "</div>";

// Test session persistence
echo "<div class='box'>";
echo "<h2>🔄 Step 3: Session Persistence Test</h2>";

if (!isset($_SESSION['test_counter'])) {
    $_SESSION['test_counter'] = 1;
    $_SESSION['test_started'] = time();
    echo "<p class='info'>✨ <strong>First visit</strong> - Session test counter initialized!</p>";
} else {
    $_SESSION['test_counter']++;
    $duration = time() - $_SESSION['test_started'];
    echo "<p class='success'>✅ <strong>SESSION PERSISTS!</strong></p>";
    echo "<pre>";
    echo "Visit Count: " . $_SESSION['test_counter'] . "\n";
    echo "Test Started: " . date('H:i:s', $_SESSION['test_started']) . "\n";
    echo "Duration: " . $duration . " seconds\n";
    echo "</pre>";
}

echo "<p><strong>Current Session Data:</strong></p>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
echo "</div>";

// Test login simulation
echo "<div class='box'>";
echo "<h2>🔐 Step 4: Login Session Test</h2>";

if (isset($_GET['simulate_login'])) {
    // Simulate admin login
    $_SESSION['user_id'] = 999;
    $_SESSION['user_name'] = 'Test Admin';
    $_SESSION['role'] = 'admin';
    $_SESSION['is_admin'] = 1;
    $_SESSION['login_time'] = time();

    echo "<p class='success'>✅ Login simulated! Refresh page to test if session persists.</p>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";

    echo "<p><a href='verify-session-fix.php' class='btn'>🔄 Refresh to Test Persistence</a></p>";
} else if (isset($_SESSION['user_id'])) {
    echo "<p class='success'>✅ LOGIN SESSION PERSISTS!</p>";
    echo "<pre>";
    echo "User ID: " . $_SESSION['user_id'] . "\n";
    echo "User Name: " . $_SESSION['user_name'] . "\n";
    echo "Role: " . $_SESSION['role'] . "\n";
    echo "Is Admin: " . $_SESSION['is_admin'] . "\n";
    echo "Login Time: " . date('H:i:s', $_SESSION['login_time']) . "\n";
    echo "Duration: " . (time() - $_SESSION['login_time']) . " seconds\n";
    echo "</pre>";

    echo "<p><strong>Auth Helper Functions:</strong></p>";
    echo "<pre>";
    echo "isLoggedIn(): " . (isLoggedIn() ? "<span class='success'>TRUE ✅</span>" : "<span class='error'>FALSE ❌</span>") . "\n";
    echo "isAdmin(): " . (isAdmin() ? "<span class='success'>TRUE ✅</span>" : "<span class='error'>FALSE ❌</span>") . "\n";
    echo "</pre>";

    echo "<p>";
    echo "<a href='?clear_login' class='btn' style='background:#f44336;'>🗑️ Clear Login</a> ";
    echo "<a href='verify-session-fix.php' class='btn'>🔄 Refresh</a>";
    echo "</p>";
} else if (isset($_GET['clear_login'])) {
    unset($_SESSION['user_id']);
    unset($_SESSION['user_name']);
    unset($_SESSION['role']);
    unset($_SESSION['is_admin']);
    unset($_SESSION['login_time']);

    echo "<p class='info'>🗑️ Login session cleared!</p>";
    echo "<p><a href='verify-session-fix.php' class='btn'>🔄 Refresh</a></p>";
} else {
    echo "<p class='info'>ℹ️ No login session active.</p>";
    echo "<p><a href='?simulate_login=1' class='btn'>🧪 Simulate Admin Login</a></p>";
}
echo "</div>";

// Final verdict
echo "<div class='box' style='border-left:5px solid #4CAF50;'>";
echo "<h2>📊 FINAL VERDICT</h2>";

$checks_passed = 0;
$total_checks = 4;

echo "<ul style='line-height:2;'>";

// Check 1: Session active
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<li><span class='success'>✅</span> Session is ACTIVE</li>";
    $checks_passed++;
} else {
    echo "<li><span class='error'>❌</span> Session is NOT active</li>";
}

// Check 2: Custom path used
if (session_save_path() === $custom_session_path) {
    echo "<li><span class='success'>✅</span> Using custom session directory</li>";
    $checks_passed++;
} else {
    echo "<li><span class='error'>❌</span> NOT using custom session directory</li>";
}

// Check 3: Custom directory writable
if (file_exists($custom_session_path) && is_writable($custom_session_path)) {
    echo "<li><span class='success'>✅</span> Custom directory is writable</li>";
    $checks_passed++;
} else {
    echo "<li><span class='error'>❌</span> Custom directory NOT writable</li>";
}

// Check 4: Session persists
if (isset($_SESSION['test_counter']) && $_SESSION['test_counter'] > 1) {
    echo "<li><span class='success'>✅</span> Session PERSISTS across page loads</li>";
    $checks_passed++;
} else {
    echo "<li><span class='info'>⏳</span> Waiting for second page load to confirm persistence</li>";
}

echo "</ul>";

echo "<h3 style='margin-top:20px;'>";
if ($checks_passed >= 3) {
    echo "<span class='success'>🎉 SESSION FIX IS WORKING! ({$checks_passed}/{$total_checks})</span>";
} else if ($checks_passed >= 2) {
    echo "<span class='info'>⚠️ PARTIAL SUCCESS ({$checks_passed}/{$total_checks})</span>";
} else {
    echo "<span class='error'>❌ SESSION FIX NOT WORKING ({$checks_passed}/{$total_checks})</span>";
}
echo "</h3>";

echo "</div>";

// Action buttons
echo "<div style='text-align:center;margin:30px 0;'>";
echo "<a href='verify-session-fix.php' class='btn'>🔄 Refresh Page</a> ";
echo "<a href='debug-admin-login.php' class='btn' style='background:#2196F3;'>🧪 Test Admin Login</a> ";
echo "<a href='admin/login.php' class='btn' style='background:#FF9800;'>🔐 Go to Admin Login</a>";
echo "</div>";

echo "<hr style='margin:40px 0;'>";
echo "<p style='text-align:center;color:#666;font-size:13px;'>Test completed at: " . date('Y-m-d H:i:s') . " | Session ID: " . substr(session_id(), 0, 16) . "...</p>";
?>
