<?php
/**
 * DEBUG CHECKOUT - Find why checkout returns error 500
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 DEBUG: Checkout Page</h1>";
echo "<style>body{font-family:monospace;padding:20px;background:#f9f9f9;} .success{color:green;font-weight:bold;} .error{color:red;font-weight:bold;} .info{color:blue;} pre{background:#fff;padding:15px;border:1px solid #ddd;margin:10px 0;overflow:auto;max-height:400px;} table{border-collapse:collapse;width:100%;margin:20px 0;background:white;} th,td{border:1px solid #ddd;padding:12px;text-align:left;} th{background:#f0f0f0;font-weight:bold;} .box{background:white;padding:20px;margin:20px 0;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);}</style>";

echo "<div class='box'>";
echo "<h2>1️⃣ Testing Basic PHP</h2>";
echo "<p class='success'>✅ PHP is working</p>";
echo "<p>PHP Version: <strong>" . phpversion() . "</strong></p>";
echo "</div>";

echo "<div class='box'>";
echo "<h2>2️⃣ Testing config.php</h2>";
try {
    require_once __DIR__ . '/config.php';
    echo "<p class='success'>✅ config.php loaded successfully</p>";
    echo "<p>Database connected: <strong>" . ($pdo ? 'YES' : 'NO') . "</strong></p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Error loading config.php: " . $e->getMessage() . "</p>";
    exit;
}
echo "</div>";

echo "<div class='box'>";
echo "<h2>3️⃣ Testing header.php Size</h2>";
$headerPath = __DIR__ . '/includes/header.php';
if (file_exists($headerPath)) {
    $size = filesize($headerPath);
    $lines = count(file($headerPath));

    echo "<table>";
    echo "<tr><th>Property</th><th>Value</th><th>Status</th></tr>";
    echo "<tr>";
    echo "<td><strong>File Size</strong></td>";
    echo "<td>" . number_format($size) . " bytes (" . round($size/1024, 2) . " KB)</td>";
    echo "<td class='" . ($size < 50000 ? 'success' : 'error') . "'>" . ($size < 50000 ? '✅ OK' : '❌ TOO LARGE') . "</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td><strong>Line Count</strong></td>";
    echo "<td>{$lines} lines</td>";
    echo "<td class='" . ($lines < 1000 ? 'success' : 'error') . "'>" . ($lines < 1000 ? '✅ OK' : '❌ TOO MANY') . "</td>";
    echo "</tr>";
    echo "</table>";

    if ($size > 50000) {
        echo "<p class='error'>⚠️ header.php is still too large! Should be under 50KB.</p>";
    }
} else {
    echo "<p class='error'>❌ header.php NOT FOUND!</p>";
}
echo "</div>";

echo "<div class='box'>";
echo "<h2>4️⃣ Testing checkout.php Load</h2>";

try {
    // Capture output
    ob_start();
    $checkoutPath = __DIR__ . '/pages/checkout.php';

    if (!file_exists($checkoutPath)) {
        echo "<p class='error'>❌ checkout.php NOT FOUND!</p>";
    } else {
        echo "<p class='info'>📄 File exists: <code>{$checkoutPath}</code></p>";
        echo "<p class='info'>Size: " . number_format(filesize($checkoutPath)) . " bytes</p>";

        // Try to include it
        try {
            include $checkoutPath;
            $output = ob_get_clean();

            echo "<p class='success'>✅ checkout.php loaded successfully!</p>";
            echo "<p>Output length: " . strlen($output) . " characters</p>";

            // Check for errors in output
            if (stripos($output, 'error') !== false || stripos($output, 'fatal') !== false) {
                echo "<p class='error'>⚠️ Found error keywords in output:</p>";
                echo "<pre>" . htmlspecialchars(substr($output, 0, 1000)) . "...</pre>";
            } else {
                echo "<p class='success'>✅ No obvious errors in output</p>";
            }

        } catch (Exception $e) {
            ob_end_clean();
            echo "<p class='error'>❌ Error including checkout.php:</p>";
            echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
            echo "<p>Line: " . $e->getLine() . "</p>";
            echo "<p>File: " . $e->getFile() . "</p>";
        }
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "<p class='error'>❌ Fatal error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

echo "<div class='box'>";
echo "<h2>5️⃣ Check PHP Error Log</h2>";
$errorLog = ini_get('error_log');
echo "<p>Error log location: <code>{$errorLog}</code></p>";

if ($errorLog && file_exists($errorLog)) {
    $errors = file_get_contents($errorLog);
    $lastErrors = array_slice(explode("\n", $errors), -20);

    echo "<p>Last 20 errors:</p>";
    echo "<pre>" . htmlspecialchars(implode("\n", $lastErrors)) . "</pre>";
} else {
    echo "<p class='info'>No error log file found or not readable</p>";
}
echo "</div>";

echo "<div class='box'>";
echo "<h2>6️⃣ Check Included Files</h2>";

echo "<table>";
echo "<tr><th>File</th><th>Exists</th><th>Size</th><th>Status</th></tr>";

$files = [
    '/includes/header.php' => 'Header',
    '/includes/footer.php' => 'Footer',
    '/includes/member-layout-start.php' => 'Member Layout',
    '/includes/helpers.php' => 'Helpers',
    '/config.php' => 'Config',
];

foreach ($files as $path => $name) {
    $fullPath = __DIR__ . $path;
    $exists = file_exists($fullPath);
    $size = $exists ? filesize($fullPath) : 0;

    echo "<tr>";
    echo "<td><strong>{$name}</strong><br><code>{$path}</code></td>";
    echo "<td>" . ($exists ? '<span class="success">✅ Yes</span>' : '<span class="error">❌ No</span>') . "</td>";
    echo "<td>" . ($exists ? number_format($size) . " bytes" : 'N/A') . "</td>";

    if (!$exists) {
        echo "<td class='error'>❌ MISSING</td>";
    } elseif ($size > 100000) {
        echo "<td class='error'>⚠️ TOO LARGE</td>";
    } else {
        echo "<td class='success'>✅ OK</td>";
    }
    echo "</tr>";
}
echo "</table>";
echo "</div>";

echo "<div class='box' style='background:#FFF3CD;border-left:4px solid #FFC107;'>";
echo "<h2>💡 Common Causes of Error 500</h2>";
echo "<ol>";
echo "<li><strong>Large file size:</strong> Files > 1MB can cause memory issues</li>";
echo "<li><strong>Infinite loop:</strong> Recursive includes or redirects</li>";
echo "<li><strong>PHP syntax error:</strong> Missing semicolon, bracket, etc.</li>";
echo "<li><strong>Memory limit:</strong> Script uses too much memory</li>";
echo "<li><strong>Timeout:</strong> Script takes too long to execute</li>";
echo "<li><strong>Missing file:</strong> Required file not found</li>";
echo "<li><strong>Database error:</strong> Query fails or connection lost</li>";
echo "</ol>";
echo "</div>";

echo "<div class='box' style='background:#E3F2FD;border-left:4px solid #2196F3;'>";
echo "<h2>🔧 Next Steps</h2>";
echo "<ol>";
echo "<li>Check if header.php is still too large (should be < 50KB)</li>";
echo "<li>Look at PHP error log for specific error message</li>";
echo "<li>Check if checkout.php has syntax errors</li>";
echo "<li>Test accessing checkout.php directly to see actual error</li>";
echo "<li>Check browser Network tab to see actual HTTP response</li>";
echo "</ol>";

echo "<div style='display:flex;gap:12px;flex-wrap:wrap;margin-top:16px;'>";
echo "<a href='/pages/checkout.php' target='_blank' style='padding:12px 24px;background:#EF4444;color:white;text-decoration:none;border-radius:6px;font-weight:bold;'>🧪 Test Checkout Page</a>";
echo "<a href='/pages/cart.php' target='_blank' style='padding:12px 24px;background:#10B981;color:white;text-decoration:none;border-radius:6px;font-weight:bold;'>🛒 View Cart</a>";
echo "<a href='/fix-admin-images.php' style='padding:12px 24px;background:#3B82F6;color:white;text-decoration:none;border-radius:6px;font-weight:bold;'>🖼️ Fix Images</a>";
echo "</div>";
echo "</div>";

echo "<hr style='margin:40px 0;'>";
echo "<p style='text-align:center;color:#666;'>Debug completed at: " . date('Y-m-d H:i:s') . "</p>";
?>
