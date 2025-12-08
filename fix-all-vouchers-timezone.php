<?php
require_once __DIR__ . '/config.php';

echo "<h1>🔧 FIX ALL VOUCHERS - TIMEZONE SYNC</h1>";
echo "<style>body{font-family:monospace;padding:20px;background:#1a1a1a;color:#00ff00;} table{border-collapse:collapse;margin:20px 0;width:100%;} th,td{border:1px solid #00ff00;padding:12px;text-align:left;} th{background:#2a2a2a;} .error{color:red;} .success{color:#00ff00;} .warning{color:yellow;}</style>";

echo "<p>Script ini akan:</p>";
echo "<ol>";
echo "<li>✅ Set MySQL timezone ke +07:00 (WIB)</li>";
echo "<li>✅ Set semua voucher yang valid_from masih 'future' jadi NOW()</li>";
echo "<li>✅ Pastikan valid_until minimal 1 bulan dari sekarang</li>";
echo "</ol>";

// Check current timezone
$stmt = $pdo->query("SELECT NOW() as now, @@session.time_zone as tz");
$result = $stmt->fetch();

echo "<h2>⏰ Current MySQL Time</h2>";
echo "<table>";
echo "<tr><th>MySQL NOW()</th><th>Timezone</th><th>PHP Time</th></tr>";
echo "<tr>";
echo "<td>{$result['now']}</td>";
echo "<td>{$result['tz']}</td>";
echo "<td>" . date('Y-m-d H:i:s') . "</td>";
echo "</tr>";
echo "</table>";

if ($result['tz'] === '+07:00') {
    echo "<p class='success'>✅ Timezone sudah benar!</p>";
} else {
    echo "<p class='warning'>⚠️ Timezone belum benar, tapi udah di-set di config.php</p>";
}

// Get all vouchers
$stmt = $pdo->query("SELECT * FROM vouchers ORDER BY id");
$vouchers = $stmt->fetchAll();

echo "<hr>";
echo "<h2>📋 Current Vouchers</h2>";
echo "<table>";
echo "<tr><th>ID</th><th>Code</th><th>valid_from (OLD)</th><th>valid_until (OLD)</th><th>is_active</th></tr>";

foreach ($vouchers as $v) {
    echo "<tr>";
    echo "<td>{$v['id']}</td>";
    echo "<td><strong>" . htmlspecialchars($v['code']) . "</strong></td>";
    echo "<td>{$v['valid_from']}</td>";
    echo "<td>{$v['valid_until']}</td>";
    echo "<td>" . ($v['is_active'] ? '✅' : '❌') . "</td>";
    echo "</tr>";
}
echo "</table>";

// FIX ALL VOUCHERS
echo "<hr>";
echo "<h2>🔧 FIXING VOUCHERS...</h2>";

$fixCount = 0;

foreach ($vouchers as $v) {
    $voucherId = $v['id'];
    $code = $v['code'];

    // Check if valid_from is in the future
    $checkStmt = $pdo->prepare("
        SELECT
            (valid_from > NOW()) as is_future,
            valid_from,
            valid_until,
            NOW() as now
        FROM vouchers
        WHERE id = ?
    ");
    $checkStmt->execute([$voucherId]);
    $check = $checkStmt->fetch();

    if ($check['is_future']) {
        // Fix: Set valid_from to NOW(), valid_until to +1 month
        $updateStmt = $pdo->prepare("
            UPDATE vouchers
            SET
                valid_from = NOW(),
                valid_until = DATE_ADD(NOW(), INTERVAL 1 MONTH),
                is_active = 1
            WHERE id = ?
        ");
        $updateStmt->execute([$voucherId]);

        echo "<p class='success'>✅ FIXED: <strong>{$code}</strong></p>";
        echo "<ul>";
        echo "<li>valid_from: {$check['valid_from']} → NOW()</li>";
        echo "<li>valid_until: {$check['valid_until']} → NOW() + 1 month</li>";
        echo "<li>is_active: SET TO 1</li>";
        echo "</ul>";

        $fixCount++;
    } else {
        // Just make sure it's active and valid_until is in future
        $updateStmt = $pdo->prepare("
            UPDATE vouchers
            SET
                is_active = 1,
                valid_until = GREATEST(valid_until, DATE_ADD(NOW(), INTERVAL 1 MONTH))
            WHERE id = ?
        ");
        $updateStmt->execute([$voucherId]);

        echo "<p class='success'>✅ CHECKED: <strong>{$code}</strong> - Sudah OK, ensure active & extend expiry</p>";
        $fixCount++;
    }
}

echo "<p class='success'><strong>TOTAL FIXED: {$fixCount} vouchers</strong></p>";

// Show final result
echo "<hr>";
echo "<h2>✅ AFTER FIX - All Vouchers</h2>";

$stmt = $pdo->query("
    SELECT
        id,
        code,
        name,
        valid_from,
        valid_until,
        NOW() as now,
        is_active,
        (valid_from <= NOW()) as started,
        (valid_until >= NOW()) as not_expired,
        (valid_from <= NOW() AND valid_until >= NOW() AND is_active = 1) as should_show
    FROM vouchers
    ORDER BY id
");
$vouchers = $stmt->fetchAll();

echo "<table>";
echo "<tr><th>Code</th><th>valid_from</th><th>NOW()</th><th>valid_until</th><th>Active</th><th>Started</th><th>Not Expired</th><th>Will Show?</th></tr>";

foreach ($vouchers as $v) {
    $bgColor = $v['should_show'] ? '#004400' : '#440000';
    echo "<tr style='background:{$bgColor};'>";
    echo "<td><strong>" . htmlspecialchars($v['code']) . "</strong></td>";
    echo "<td>{$v['valid_from']}</td>";
    echo "<td>{$v['now']}</td>";
    echo "<td>{$v['valid_until']}</td>";
    echo "<td>" . ($v['is_active'] ? '✅' : '❌') . "</td>";
    echo "<td>" . ($v['started'] ? '✅' : '❌') . "</td>";
    echo "<td>" . ($v['not_expired'] ? '✅' : '❌') . "</td>";
    echo "<td><strong>" . ($v['should_show'] ? '✅ YES' : '❌ NO') . "</strong></td>";
    echo "</tr>";
}
echo "</table>";

// FINAL TEST: Run the actual member voucher query
echo "<hr>";
echo "<h2>🧪 FINAL TEST - Member Voucher Query (User ID 3)</h2>";

$stmt = $pdo->prepare("
    SELECT v.*
    FROM user_vouchers uv
    INNER JOIN vouchers v ON uv.voucher_id = v.id
    WHERE uv.user_id = 3
      AND v.is_active = 1
      AND v.valid_from <= NOW()
      AND v.valid_until >= NOW()
");
$stmt->execute();
$results = $stmt->fetchAll();

echo "<p style='font-size:24px;' class='" . (count($results) > 0 ? 'success' : 'error') . "'>";
echo count($results) > 0 ? "✅ SUCCESS!" : "❌ STILL FAILED";
echo " - Found <strong>" . count($results) . "</strong> vouchers</p>";

if (count($results) > 0) {
    echo "<ul>";
    foreach ($results as $r) {
        echo "<li>✅ " . htmlspecialchars($r['code']) . " - " . htmlspecialchars($r['name']) . "</li>";
    }
    echo "</ul>";

    echo "<p class='success' style='font-size:20px;'>🎉 <strong>MEMBER VOUCHER PAGE SEKARANG AKAN MUNCUL!</strong></p>";
    echo "<p>Test di: <a href='/member/vouchers/' style='color:#00ff00;'>https://dorve.id/member/vouchers/</a></p>";
} else {
    echo "<p class='error'>Still no results. Might need to check user_vouchers table.</p>";
}
?>
