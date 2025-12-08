<?php
/**
 * AUTO-FIX VOUCHER DATES & STATUS
 * Makes all vouchers active and valid
 */

require_once __DIR__ . '/config.php';

// Admin check
if (!isLoggedIn() || !in_array($_SESSION['email'], ['admin@dorve.id', 'admin1@dorve.id', 'admin2@dorve.id', 'admin@dorve.co', 'admin1@dorve.co', 'admin2@dorve.co'])) {
    die('Admin access required');
}

echo "<h1>🔧 AUTO-FIX VOUCHER DATES</h1>";
echo "<style>body{font-family:monospace;padding:20px;background:#f9f9f9;} .success{color:green;font-weight:bold;} .error{color:red;font-weight:bold;} pre{background:#fff;padding:15px;border:1px solid #ddd;margin:10px 0;} .box{background:white;padding:20px;margin:20px 0;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);}</style>";

echo "<div class='box'>";
echo "<h2>🔍 Checking All Vouchers...</h2>";

try {
    // Get all vouchers
    $stmt = $pdo->query("SELECT * FROM vouchers ORDER BY id ASC");
    $vouchers = $stmt->fetchAll();

    echo "<p>Found: <strong>" . count($vouchers) . "</strong> vouchers</p>";

    $fixed = 0;
    $alreadyOk = 0;

    foreach ($vouchers as $v) {
        $needsFix = false;
        $fixes = [];

        echo "<div style='margin:20px 0;padding:15px;background:#F9FAFB;border-radius:8px;'>";
        echo "<h3 style='margin:0 0 10px 0;'>🎫 {$v['code']} - {$v['name']}</h3>";

        // Check is_active
        if ($v['is_active'] != 1) {
            $fixes[] = "is_active: {$v['is_active']} → 1";
            $needsFix = true;
        }

        // Check valid_from (should be in past)
        if (strtotime($v['valid_from']) > time()) {
            $fixes[] = "valid_from: {$v['valid_from']} → " . date('Y-m-d H:i:s');
            $needsFix = true;
        }

        // Check valid_until (should be in future)
        if (strtotime($v['valid_until']) < time()) {
            $futureDate = date('Y-m-d H:i:s', strtotime('+30 days'));
            $fixes[] = "valid_until: {$v['valid_until']} → {$futureDate}";
            $needsFix = true;
        }

        if ($needsFix) {
            echo "<p class='error'>❌ Issues found:</p>";
            echo "<ul>";
            foreach ($fixes as $fix) {
                echo "<li>{$fix}</li>";
            }
            echo "</ul>";

            // Apply fix
            $updateSql = "UPDATE vouchers SET
                            is_active = 1,
                            valid_from = IF(valid_from > NOW(), NOW(), valid_from),
                            valid_until = IF(valid_until < NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), valid_until)
                          WHERE id = ?";

            $stmt = $pdo->prepare($updateSql);
            if ($stmt->execute([$v['id']])) {
                echo "<p class='success'>✅ FIXED!</p>";
                $fixed++;
            } else {
                echo "<p class='error'>❌ Fix failed</p>";
            }
        } else {
            echo "<p class='success'>✅ Already OK</p>";
            $alreadyOk++;
        }

        echo "</div>";
    }

    echo "</div>";

    echo "<div class='box' style='background:#D1FAE5;border-left:4px solid #10B981;'>";
    echo "<h2>📊 Summary</h2>";
    echo "<table style='border-collapse:collapse;width:100%;background:white;'>";
    echo "<tr style='border:1px solid #ddd;'><th style='padding:12px;background:#f0f0f0;'>Result</th><th style='padding:12px;background:#f0f0f0;'>Count</th></tr>";
    echo "<tr style='border:1px solid #ddd;'><td style='padding:12px;'>Total Vouchers</td><td style='padding:12px;'><strong>" . count($vouchers) . "</strong></td></tr>";
    echo "<tr style='border:1px solid #ddd;'><td style='padding:12px;'>Fixed</td><td style='padding:12px;'><strong class='success'>{$fixed}</strong></td></tr>";
    echo "<tr style='border:1px solid #ddd;'><td style='padding:12px;'>Already OK</td><td style='padding:12px;'><strong>{$alreadyOk}</strong></td></tr>";
    echo "</table>";
    echo "</div>";

    if ($fixed > 0) {
        echo "<div class='box' style='background:#E3F2FD;border-left:4px solid #2196F3;'>";
        echo "<h2>🎉 Vouchers Fixed!</h2>";
        echo "<p><strong>All vouchers are now:</strong></p>";
        echo "<ul>";
        echo "<li>✅ Active (is_active = 1)</li>";
        echo "<li>✅ Started (valid_from <= NOW)</li>";
        echo "<li>✅ Valid for 30 days (valid_until > NOW)</li>";
        echo "</ul>";
        echo "<p style='margin-top:20px;'><strong>Test now:</strong></p>";
        echo "<div style='display:flex;gap:12px;flex-wrap:wrap;'>";
        echo "<a href='/debug-vouchers.php' style='padding:12px 24px;background:#8B5CF6;color:white;text-decoration:none;border-radius:6px;font-weight:bold;'>🔍 Debug Vouchers</a>";
        echo "<a href='/member/vouchers/' style='padding:12px 24px;background:#10B981;color:white;text-decoration:none;border-radius:6px;font-weight:bold;'>👀 View My Vouchers</a>";
        echo "</div>";
        echo "</div>";
    }

} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr style='margin:40px 0;'>";
echo "<p style='text-align:center;color:#666;'>Completed at: " . date('Y-m-d H:i:s') . "</p>";
?>
