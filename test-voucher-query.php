<?php
/**
 * TEST VOUCHER QUERY AFTER FIX
 * Check if vouchers are really fixed
 */

require_once __DIR__ . '/config.php';

if (!isLoggedIn()) {
    die('Please login first');
}

$userId = $_SESSION['user_id'];
$user = getCurrentUser();

echo "<h1>🧪 TEST VOUCHER QUERY AFTER FIX</h1>";
echo "<style>body{font-family:monospace;padding:20px;background:#f9f9f9;} .success{color:green;font-weight:bold;} .error{color:red;font-weight:bold;} .warning{color:orange;font-weight:bold;} table{border-collapse:collapse;width:100%;margin:20px 0;background:white;} th,td{border:1px solid #ddd;padding:12px;text-align:left;} th{background:#f0f0f0;} .box{background:white;padding:20px;margin:20px 0;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);}</style>";

echo "<div class='box'>";
echo "<h2>👤 Your Info</h2>";
echo "<p>User ID: <strong>{$userId}</strong></p>";
echo "<p>Email: <strong>" . htmlspecialchars($user['email']) . "</strong></p>";
echo "<p>Current Time: <strong>" . date('Y-m-d H:i:s') . "</strong></p>";
echo "</div>";

// 1. Check vouchers assigned to this user
echo "<div class='box'>";
echo "<h2>1️⃣ Your Assigned Vouchers (from user_vouchers)</h2>";

$stmt = $pdo->prepare("SELECT * FROM user_vouchers WHERE user_id = ?");
$stmt->execute([$userId]);
$userVouchers = $stmt->fetchAll();

echo "<p>Count: <strong>" . count($userVouchers) . "</strong></p>";

if (!empty($userVouchers)) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Voucher ID</th><th>Assigned At</th></tr>";
    foreach ($userVouchers as $uv) {
        echo "<tr>";
        echo "<td>{$uv['id']}</td>";
        echo "<td>{$uv['voucher_id']}</td>";
        echo "<td>{$uv['assigned_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    $voucherIds = array_column($userVouchers, 'voucher_id');

    // 2. Check the actual voucher details
    echo "</div><div class='box'>";
    echo "<h2>2️⃣ Voucher Details (from vouchers table)</h2>";

    $placeholders = str_repeat('?,', count($voucherIds) - 1) . '?';
    $stmt = $pdo->prepare("SELECT * FROM vouchers WHERE id IN ($placeholders)");
    $stmt->execute($voucherIds);
    $vouchers = $stmt->fetchAll();

    echo "<table>";
    echo "<tr>";
    echo "<th>ID</th>";
    echo "<th>Code</th>";
    echo "<th>Name</th>";
    echo "<th>is_active</th>";
    echo "<th>valid_from</th>";
    echo "<th>valid_until</th>";
    echo "<th>Status</th>";
    echo "</tr>";

    $now = time();

    foreach ($vouchers as $v) {
        $isActive = $v['is_active'] == 1;
        $hasStarted = strtotime($v['valid_from']) <= $now;
        $notExpired = strtotime($v['valid_until']) >= $now;
        $shouldShow = $isActive && $hasStarted && $notExpired;

        $bgColor = $shouldShow ? '#D1FAE5' : '#FEE2E2';

        echo "<tr style='background:{$bgColor};'>";
        echo "<td>{$v['id']}</td>";
        echo "<td><strong>" . htmlspecialchars($v['code']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($v['name']) . "</td>";
        echo "<td><span class='" . ($isActive ? 'success' : 'error') . "'>{$v['is_active']}</span></td>";
        echo "<td>{$v['valid_from']}</td>";
        echo "<td>{$v['valid_until']}</td>";
        echo "<td>";

        if ($shouldShow) {
            echo "<span class='success'>✅ SHOULD SHOW</span>";
        } else {
            echo "<span class='error'>❌ FILTERED OUT</span><br>";
            if (!$isActive) echo "→ is_active = 0<br>";
            if (!$hasStarted) echo "→ valid_from in future<br>";
            if (!$notExpired) echo "→ valid_until expired<br>";
        }

        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // 3. Test the EXACT query from member/vouchers/index.php
    echo "</div><div class='box' style='background:#FFF3CD;border-left:4px solid #F59E0B;'>";
    echo "<h2>3️⃣ Test EXACT Query (same as member/vouchers page)</h2>";

    $stmt = $pdo->prepare("
        SELECT v.*,
               uv.assigned_at
        FROM user_vouchers uv
        INNER JOIN vouchers v ON uv.voucher_id = v.id
        WHERE uv.user_id = ?
          AND v.is_active = 1
          AND v.valid_from <= NOW()
          AND v.valid_until >= NOW()
        ORDER BY v.type DESC, v.discount_value DESC
    ");
    $stmt->execute([$userId]);
    $results = $stmt->fetchAll();

    echo "<p><strong>Query Result:</strong> <span class='" . (count($results) > 0 ? 'success' : 'error') . "'>" . count($results) . " vouchers</span></p>";

    if (count($results) > 0) {
        echo "<table>";
        echo "<tr><th>Code</th><th>Name</th><th>Type</th><th>Assigned</th></tr>";
        foreach ($results as $r) {
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($r['code']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($r['name']) . "</td>";
            echo "<td>" . htmlspecialchars($r['type']) . "</td>";
            echo "<td>" . $r['assigned_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";

        echo "<p class='success'>✅ Vouchers SHOULD appear on member/vouchers page!</p>";
    } else {
        echo "<p class='error'>❌ No vouchers match the query!</p>";
        echo "<p><strong>This means:</strong></p>";
        echo "<ul>";
        echo "<li>Vouchers might not be active (is_active = 0)</li>";
        echo "<li>Vouchers might not have started (valid_from > NOW)</li>";
        echo "<li>Vouchers might be expired (valid_until < NOW)</li>";
        echo "</ul>";

        echo "<p style='margin-top:20px;'><strong>Did you run the fix script?</strong></p>";
        echo "<a href='/fix-voucher-dates.php' style='padding:12px 24px;background:#3B82F6;color:white;text-decoration:none;border-radius:6px;font-weight:bold;display:inline-block;'>🔧 Run Fix Script</a>";
    }

    echo "</div>";

} else {
    echo "<p class='error'>❌ No vouchers assigned to you!</p>";
}
echo "</div>";

// 4. Final verdict
echo "<div class='box' style='background:#E3F2FD;border-left:4px solid #2196F3;'>";
echo "<h2>🎯 FINAL VERDICT</h2>";

if (!empty($userVouchers)) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM user_vouchers uv
        INNER JOIN vouchers v ON uv.voucher_id = v.id
        WHERE uv.user_id = ?
          AND v.is_active = 1
          AND v.valid_from <= NOW()
          AND v.valid_until >= NOW()
    ");
    $stmt->execute([$userId]);
    $count = $stmt->fetch()['count'];

    if ($count > 0) {
        echo "<p class='success' style='font-size:18px;'>✅ SUCCESS! {$count} voucher(s) should show on member page!</p>";
        echo "<p>If they still don't show:</p>";
        echo "<ul>";
        echo "<li>Clear browser cache (Ctrl+Shift+R)</li>";
        echo "<li>Try incognito/private mode</li>";
        echo "<li>Check <a href='/member/vouchers/'>member/vouchers page</a> now</li>";
        echo "</ul>";
    } else {
        echo "<p class='error' style='font-size:18px;'>❌ STILL NOT SHOWING!</p>";
        echo "<p><strong>Reason:</strong> Vouchers don't pass the filters</p>";
        echo "<p><strong>Solution:</strong> Run fix-voucher-dates.php to activate them</p>";
        echo "<a href='/fix-voucher-dates.php' style='padding:12px 24px;background:#EF4444;color:white;text-decoration:none;border-radius:6px;font-weight:bold;display:inline-block;margin-top:12px;'>🔧 FIX NOW</a>";
    }
} else {
    echo "<p class='warning' style='font-size:18px;'>⚠️ No vouchers assigned to you</p>";
    echo "<p>Contact admin to assign vouchers to your account</p>";
}

echo "</div>";

echo "<hr style='margin:40px 0;'>";
echo "<p style='text-align:center;color:#666;'>Test completed at: " . date('Y-m-d H:i:s') . "</p>";
?>
