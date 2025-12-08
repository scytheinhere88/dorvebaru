<?php
// SESSION FIX & DIAGNOSTIC TOOL
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 SESSION FIX & DIAGNOSTIC</h1>";
echo "<style>body{font-family:monospace;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} pre{background:#f5f5f5;padding:10px;border-radius:5px;}</style>";

// 1. Check current session configuration
echo "<h2>📋 Current Session Configuration</h2>";
echo "<pre>";
echo "session.save_path: " . ini_get('session.save_path') . "\n";
echo "session.save_handler: " . ini_get('session.save_handler') . "\n";
echo "session.name: " . ini_get('session.name') . "\n";
echo "session.cookie_lifetime: " . ini_get('session.cookie_lifetime') . "\n";
echo "session.cookie_path: " . ini_get('session.cookie_path') . "\n";
echo "session.cookie_domain: " . ini_get('session.cookie_domain') . "\n";
echo "session.cookie_secure: " . ini_get('session.cookie_secure') . "\n";
echo "session.cookie_httponly: " . ini_get('session.cookie_httponly') . "\n";
echo "session.cookie_samesite: " . ini_get('session.cookie_samesite') . "\n";
echo "session.gc_probability: " . ini_get('session.gc_probability') . "\n";
echo "session.gc_divisor: " . ini_get('session.gc_divisor') . "\n";
echo "</pre>";

// 2. Check session save path
$save_path = session_save_path();
if (empty($save_path)) {
    $save_path = sys_get_temp_dir();
}

echo "<h2>📁 Session Save Path</h2>";
echo "<pre>";
echo "Path: " . $save_path . "\n";
echo "Exists: " . (file_exists($save_path) ? "✅ YES" : "❌ NO") . "\n";
if (file_exists($save_path)) {
    echo "Is Directory: " . (is_dir($save_path) ? "✅ YES" : "❌ NO") . "\n";
    echo "Is Writable: " . (is_writable($save_path) ? "✅ YES" : "❌ NO") . "\n";
    echo "Is Readable: " . (is_readable($save_path) ? "✅ YES" : "❌ NO") . "\n";
    $perms = fileperms($save_path);
    echo "Permissions: " . substr(sprintf('%o', $perms), -4) . "\n";
    $owner = posix_getpwuid(fileowner($save_path));
    echo "Owner: " . $owner['name'] . "\n";
    $current_user = posix_getpwuid(posix_geteuid());
    echo "Current PHP User: " . $current_user['name'] . "\n";
}
echo "</pre>";

// 3. Try to create custom session directory in project
$custom_session_path = __DIR__ . '/sessions';
echo "<h2>🛠️ Custom Session Directory</h2>";
echo "<pre>";
echo "Custom Path: " . $custom_session_path . "\n";

if (!file_exists($custom_session_path)) {
    echo "Status: Does not exist, creating...\n";
    if (mkdir($custom_session_path, 0755, true)) {
        echo "✅ Directory created successfully\n";
    } else {
        echo "❌ Failed to create directory\n";
    }
} else {
    echo "Status: Already exists\n";
}

if (file_exists($custom_session_path)) {
    chmod($custom_session_path, 0755);
    echo "Permissions set to: 0755\n";
    echo "Is Writable: " . (is_writable($custom_session_path) ? "✅ YES" : "❌ NO") . "\n";
}
echo "</pre>";

// 4. Test session with custom path
echo "<h2>🧪 Testing Session with Custom Path</h2>";
session_save_path($custom_session_path);
session_start();

echo "<pre>";
echo "Session Started: " . (session_status() === PHP_SESSION_ACTIVE ? "✅ YES" : "❌ NO") . "\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Save Path: " . session_save_path() . "\n";

// Set test data
$_SESSION['test_key'] = 'test_value_' . time();
$_SESSION['test_time'] = time();

echo "\nTest data set:\n";
print_r($_SESSION);
echo "</pre>";

// 5. Generate fixed config.php code
echo "<h2>🔧 FIX CODE for config.php</h2>";
echo "<p>Add this code BEFORE session_start() in config.php:</p>";
echo "<pre style='background:#ffe;border:2px solid #fa0;'>";
echo htmlspecialchars("
// Fix session save path
\$session_dir = __DIR__ . '/sessions';
if (!file_exists(\$session_dir)) {
    mkdir(\$session_dir, 0755, true);
}
session_save_path(\$session_dir);
");
echo "</pre>";

// 6. Check for .htaccess session config
echo "<h2>📄 Check .htaccess</h2>";
$htaccess_file = __DIR__ . '/.htaccess';
if (file_exists($htaccess_file)) {
    $htaccess_content = file_get_contents($htaccess_file);
    if (strpos($htaccess_content, 'php_value session.save_path') !== false) {
        echo "<p class='error'>⚠️ .htaccess contains session.save_path directive - this may conflict!</p>";
        echo "<pre>" . htmlspecialchars($htaccess_content) . "</pre>";
    } else {
        echo "<p class='success'>✅ .htaccess does not contain session directives</p>";
    }
} else {
    echo "<p class='info'>ℹ️ No .htaccess file found</p>";
}

// 7. Check for .user.ini
echo "<h2>📄 Check .user.ini</h2>";
$user_ini = __DIR__ . '/.user.ini';
if (file_exists($user_ini)) {
    $user_ini_content = file_get_contents($user_ini);
    echo "<pre>" . htmlspecialchars($user_ini_content) . "</pre>";
} else {
    echo "<p class='info'>ℹ️ No .user.ini file found</p>";
}

// 8. Check session files
echo "<h2>📝 Session Files in Custom Directory</h2>";
if (file_exists($custom_session_path)) {
    $files = glob($custom_session_path . '/sess_*');
    echo "<pre>";
    echo "Total session files: " . count($files) . "\n";
    if (count($files) > 0) {
        echo "\nRecent session files:\n";
        $files = array_slice($files, -5); // Last 5 files
        foreach ($files as $file) {
            echo basename($file) . " - " . date('Y-m-d H:i:s', filemtime($file)) . " - " . filesize($file) . " bytes\n";
        }
    }
    echo "</pre>";
}

// 9. Action buttons
echo "<h2>🎯 Actions</h2>";
echo "<p>";
echo "<a href='?action=apply_fix' style='display:inline-block;padding:10px 20px;background:#4CAF50;color:white;text-decoration:none;border-radius:5px;margin:5px;'>✅ Apply Fix to config.php</a>";
echo "<a href='?action=test_login' style='display:inline-block;padding:10px 20px;background:#2196F3;color:white;text-decoration:none;border-radius:5px;margin:5px;'>🧪 Test Admin Login</a>";
echo "<a href='debug-admin-login.php' style='display:inline-block;padding:10px 20px;background:#FF9800;color:white;text-decoration:none;border-radius:5px;margin:5px;'>🔍 Debug Login Flow</a>";
echo "</p>";

// Handle actions
if (isset($_GET['action']) && $_GET['action'] === 'apply_fix') {
    echo "<h2>🔧 Applying Fix...</h2>";

    $config_file = __DIR__ . '/config.php';
    $config_content = file_get_contents($config_file);

    // Check if fix already applied
    if (strpos($config_content, 'session_save_path') !== false) {
        echo "<p class='info'>ℹ️ Fix already applied to config.php</p>";
    } else {
        // Add fix before session_start
        $fix_code = "\n// Fix session save path\n\$session_dir = __DIR__ . '/sessions';\nif (!file_exists(\$session_dir)) {\n    mkdir(\$session_dir, 0755, true);\n}\nsession_save_path(\$session_dir);\n\n";

        $config_content = str_replace(
            "// Session Configuration",
            "// Session Configuration" . $fix_code,
            $config_content
        );

        if (file_put_contents($config_file, $config_content)) {
            echo "<p class='success'>✅ Fix applied successfully to config.php!</p>";
            echo "<p><strong>Next steps:</strong></p>";
            echo "<ol>";
            echo "<li>Clear browser cache and cookies</li>";
            echo "<li><a href='debug-admin-login.php'>Test login again</a></li>";
            echo "</ol>";
        } else {
            echo "<p class='error'>❌ Failed to write to config.php - check file permissions</p>";
        }
    }
}

echo "<hr>";
echo "<p><small>Generated at: " . date('Y-m-d H:i:s') . "</small></p>";
?>
