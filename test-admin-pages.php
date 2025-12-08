<?php
/**
 * TEST ADMIN PAGES ACCESS
 * - Test if settings and error logs are accessible
 * - Check for redirects
 * - Verify authentication
 */

require_once __DIR__ . '/config.php';

echo "<h1>🔍 Test Admin Pages Access</h1>";
echo "<style>body{font-family:monospace;padding:20px;background:#f9f9f9;} .success{color:green;font-weight:bold;} .error{color:red;font-weight:bold;} .info{color:blue;} .box{background:white;padding:20px;margin:20px 0;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);} table{border-collapse:collapse;width:100%;margin:20px 0;background:white;} th,td{border:1px solid #ddd;padding:12px;text-align:left;} th{background:#f0f0f0;}</style>";

echo "<div class='box'>";
echo "<h2>Session Status</h2>";
echo "<table>";
echo "<tr><th>Key</th><th>Value</th></tr>";
echo "<tr><td><strong>Session ID</strong></td><td>" . session_id() . "</td></tr>";
echo "<tr><td><strong>User ID</strong></td><td>" . ($_SESSION['user_id'] ?? 'NOT SET') . "</td></tr>";
echo "<tr><td><strong>Role</strong></td><td>" . ($_SESSION['role'] ?? 'NOT SET') . "</td></tr>";
echo "<tr><td><strong>isAdmin()</strong></td><td>" . (isAdmin() ? '<span class="success">✅ TRUE</span>' : '<span class="error">❌ FALSE</span>') . "</td></tr>";
echo "<tr><td><strong>isLoggedIn()</strong></td><td>" . (isLoggedIn() ? '<span class="success">✅ TRUE</span>' : '<span class="error">❌ FALSE</span>') . "</td></tr>";
echo "</table>";
echo "</div>";

if (!isAdmin()) {
    echo "<div style='background:#FEE2E2;padding:20px;margin:20px 0;border-left:4px solid #DC2626;'>";
    echo "<h2>❌ NOT LOGGED IN AS ADMIN!</h2>";
    echo "<p>You need to be logged in as admin to access admin pages.</p>";
    echo "<p><a href='/admin/login.php'>Login as Admin</a></p>";
    echo "</div>";
    exit;
}

echo "<div class='box'>";
echo "<h2>🗂️ File System Check</h2>";

$files = [
    '/admin/settings/index.php' => 'General Settings',
    '/admin/settings/payment-settings.php' => 'Payment Settings',
    '/admin/settings/bank-accounts.php' => 'Bank Accounts',
    '/admin/settings/referral-settings.php' => 'Referral Settings',
    '/admin/settings/api-settings.php' => 'API Settings',
    '/admin/integration/error-logs.php' => 'Error & Webhook Logs',
    '/admin/includes/admin-header.php' => 'Admin Header',
    '/admin/includes/admin-footer.php' => 'Admin Footer',
];

echo "<table>";
echo "<tr><th>File</th><th>Exists</th><th>Readable</th><th>Size</th></tr>";

foreach ($files as $path => $name) {
    $fullPath = __DIR__ . $path;
    $exists = file_exists($fullPath);
    $readable = $exists && is_readable($fullPath);
    $size = $exists ? filesize($fullPath) : 0;

    echo "<tr>";
    echo "<td><strong>{$name}</strong><br><code>{$path}</code></td>";
    echo "<td>" . ($exists ? '<span class="success">✅</span>' : '<span class="error">❌</span>') . "</td>";
    echo "<td>" . ($readable ? '<span class="success">✅</span>' : '<span class="error">❌</span>') . "</td>";
    echo "<td>" . ($exists ? number_format($size) . " bytes" : 'N/A') . "</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

echo "<div class='box'>";
echo "<h2>🔗 Test Page Access</h2>";
echo "<p>Click the links below to test if pages load correctly:</p>";

$testPages = [
    '/admin/settings/index.php' => '⚙️ General Settings',
    '/admin/settings/payment-settings.php' => '💳 Payment Settings',
    '/admin/settings/bank-accounts.php' => '🏦 Bank Accounts',
    '/admin/integration/error-logs.php' => '📊 Error & Webhook Logs',
    '/admin/products/' => '📦 Products',
    '/admin/orders/' => '🛒 Orders',
    '/admin/vouchers/' => '🎫 Vouchers',
];

echo "<div style='display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:16px;margin-top:20px;'>";
foreach ($testPages as $path => $name) {
    echo "<a href='{$path}' target='_blank' style='display:block;padding:20px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;text-decoration:none;border-radius:8px;text-align:center;font-weight:bold;transition:transform 0.2s;'>";
    echo "<div style='font-size:24px;margin-bottom:8px;'>{$name}</div>";
    echo "<div style='font-size:12px;opacity:0.9;'>{$path}</div>";
    echo "</a>";
}
echo "</div>";
echo "</div>";

echo "<div class='box' style='background:#FFF3CD;border-left:4px solid #FFC107;'>";
echo "<h2>⚠️ Troubleshooting Tips</h2>";
echo "<ol>";
echo "<li><strong>If page redirects to dashboard:</strong>";
echo "<ul>";
echo "<li>Check browser console for JavaScript errors (F12 → Console)</li>";
echo "<li>Check browser Network tab to see HTTP response code</li>";
echo "<li>Look for PHP errors in server error log</li>";
echo "<li>Verify file permissions (should be 644 for files, 755 for directories)</li>";
echo "</ul></li>";
echo "<li><strong>If page shows blank/white screen:</strong>";
echo "<ul>";
echo "<li>PHP fatal error - check server error log</li>";
echo "<li>Missing database table - check PHP errors</li>";
echo "<li>Memory limit exceeded - increase PHP memory_limit</li>";
echo "</ul></li>";
echo "<li><strong>If page shows 404:</strong>";
echo "<ul>";
echo "<li>File doesn't exist - verify file path</li>";
echo "<li>.htaccess rewrite issue - check mod_rewrite</li>";
echo "</ul></li>";
echo "</ol>";
echo "</div>";

echo "<div class='box'>";
echo "<h2>🔧 Quick Actions</h2>";
echo "<div style='display:flex;gap:12px;flex-wrap:wrap;'>";
echo "<a href='/admin/' style='padding:12px 24px;background:#10B981;color:white;text-decoration:none;border-radius:6px;font-weight:bold;'>← Back to Dashboard</a>";
echo "<a href='/fix-admin-images.php' style='padding:12px 24px;background:#3B82F6;color:white;text-decoration:none;border-radius:6px;font-weight:bold;'>🖼️ Fix Product Images</a>";
echo "<a href='/fix-voucher-system.php' style='padding:12px 24px;background:#8B5CF6;color:white;text-decoration:none;border-radius:6px;font-weight:bold;'>🎫 Fix Vouchers</a>";
echo "</div>";
echo "</div>";

echo "<hr style='margin:40px 0;'>";
echo "<p style='text-align:center;color:#666;'>Completed at: " . date('Y-m-d H:i:s') . "</p>";
?>
