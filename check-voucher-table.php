<?php
/**
 * CHECK VOUCHER TABLE DATA
 * Find why vouchers are filtered out
 */

require_once __DIR__ . '/config.php';

if (!isLoggedIn()) {
    die('Login first!');
}

$userId = $_SESSION['user_id'];
$user = getCurrentUser();

echo "<h1>🔍 CHECK VOUCHER DATA</h1>";
echo "<style>body{font-family:monospace;padding:20px;background:#f9f9f9;} .success{color:green;font-weight:bold;} .error{color:red;font-weight:bold;} .warning{color:orange;font-weight:bold;} table{border-collapse:collapse;width:100%;margin:20px 0;background:white;} th,td{border:1px solid #ddd;padding:12px;text-align:left;} th{background:#f0f0f0;font-weight:bold;} .box{background:white;padding:20px;margin:20px 0;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);}</style>";

echo "<div class='box'>";
echo "<h2>👤 Your Info</h2>";
echo "<p>User ID: <strong>{$userId}</strong></p>";
echo "<p>Email: <strong>" . htmlspecialchars($user['email']) . "</strong></p>";
echo "<p>Tier: <strong>" . strtoupper($user['tier'] ?? 'none') . "</strong></p>";
echo "</div>";

// 1. Check which vouchers you have in user_vouchers
echo "<div class='box'>";
echo "<h2>1️⃣ Your Assigned Vouchers (from user_vouchers)</h2>";

$stmt = $pdo->prepare("
    SELECT uv.*, v.code, v.name
    FROM user_vouchers uv
    JOIN vouchers v ON uv.voucher_id = v.id
    WHERE uv.user_id = ?
");
$stmt->execute([$userId]);
$userVouchers = $stmt->fetchAll();

echo "<p>Found: <strong>" . count($userVouchers) . "</strong> voucher assignments</p>";

if (!empty($userVouchers)) {
    echo "<table>";
    echo "<tr><th>Voucher ID</th><th>Code</th><th>Name</th><th>Assigned At</th></tr>";
    foreach ($userVouchers as $uv) {
        echo "<tr>";
        echo "<td>{$uv['voucher_id']}</td>";
        echo "<td><strong>" . htmlspecialchars($uv['code']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($uv['name']) . "</td>";
        echo "<td>" . $uv['assigned_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
echo "</div>";

// 2. Check FULL details of those vouchers
echo "<div class='box'>";
echo "<h2>2️⃣ Full Voucher Details (from vouchers table)</h2>";

if (!empty($userVouchers)) {
    $voucherIds = array_column($userVouchers, 'voucher_id');
    $placeholders = str_repeat('?,', count($voucherIds) - 1) . '?';

    $stmt = $pdo->prepare("
        SELECT *
        FROM vouchers
        WHERE id IN ($placeholders)
    ");
    $stmt->execute($voucherIds);
    $voucherDetails = $stmt->fetchAll();

    echo "<table>";
    echo "<tr>";
    echo "<th>ID</th>";
    echo "<th>Code</th>";
    echo "<th>Name</th>";
    echo "<th>is_active</th>";
    echo "<th>valid_from</th>";
    echo "<th>valid_until</th>";
    echo "<th>NOW()</th>";
    echo "<th>Status</th>";
    echo "</tr>";

    $now = date('Y-m-d H:i:s');

    foreach ($voucherDetails as $v) {
        $isActive = $v['is_active'] == 1;
        $hasStarted = strtotime($v['valid_from']) <= time();
        $notExpired = strtotime($v['valid_until']) >= time();
        $shouldShow = $isActive && $hasStarted && $notExpired;

        echo "<tr style='background:" . ($shouldShow ? '#D1FAE5' : '#FEE2E2') . ";'>";
        echo "<td>{$v['id']}</td>";
        echo "<td><strong>" . htmlspecialchars($v['code']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($v['name']) . "</td>";
        echo "<td class='" . ($isActive ? 'success' : 'error') . "'>" . ($isActive ? '✅ 1' : '❌ 0') . "</td>";
        echo "<td>{$v['valid_from']}</td>";
        echo "<td>{$v['valid_until']}</td>";
        echo "<td>{$now}</td>";
        echo "<td>";

        if ($shouldShow) {
            echo "<span class='success'>✅ SHOULD SHOW</span>";
        } else {
            echo "<span class='error'>❌ FILTERED OUT</span><br>";
            if (!$isActive) echo "• Not active (is_active = 0)<br>";
            if (!$hasStarted) echo "• Not started yet (valid_from > NOW)<br>";
            if (!$notExpired) echo "• Expired (valid_until < NOW)<br>";
        }

        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
echo "</div>";

// 3. Show the filtering conditions
echo "<div class='box' style='background:#FFF3CD;border-left:4px solid #F59E0B;'>";
echo "<h2>🔍 Why Vouchers Not Showing</h2>";

echo "<p><strong>Current Query Filters:</strong></p>";
echo "<pre>WHERE v.is_active = 1           -- Must be active
  AND v.valid_from <= NOW()     -- Must have started
  AND v.valid_until >= NOW()    -- Must not be expired</pre>";

echo "<p><strong>Current Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

if (!empty($voucherDetails)) {
    $issues = [];
    foreach ($voucherDetails as $v) {
        $code = $v['code'];

        if ($v['is_active'] != 1) {
            $issues[] = "❌ <strong>{$code}</strong>: is_active = {$v['is_active']} (needs to be 1)";
        }

        if (strtotime($v['valid_from']) > time()) {
            $issues[] = "❌ <strong>{$code}</strong>: valid_from = {$v['valid_from']} (starts in future, should be before NOW)";
        }

        if (strtotime($v['valid_until']) < time()) {
            $issues[] = "❌ <strong>{$code}</strong>: valid_until = {$v['valid_until']} (expired, should be after NOW)";
        }
    }

    if (empty($issues)) {
        echo "<p class='success'>✅ All vouchers pass the filters! They should show up.</p>";
        echo "<p class='warning'>⚠️ If they still don't show, there's a different issue.</p>";
    } else {
        echo "<p class='error'><strong>Issues Found:</strong></p>";
        echo "<ul>";
        foreach ($issues as $issue) {
            echo "<li>{$issue}</li>";
        }
        echo "</ul>";
    }
}
echo "</div>";

// 4. Provide fix link
echo "<div class='box' style='background:#E3F2FD;border-left:4px solid #2196F3;'>";
echo "<h2>🔧 How to Fix</h2>";
echo "<p>Click the button below to automatically fix all voucher dates and activate them:</p>";
echo "<a href='/fix-voucher-dates.php' style='display:inline-block;padding:12px 24px;background:#3B82F6;color:white;text-decoration:none;border-radius:6px;font-weight:bold;margin-top:12px;'>🔧 Auto-Fix All Vouchers</a>";
echo "</div>";

echo "<hr style='margin:40px 0;'>";
echo "<p style='text-align:center;color:#666;'>Check completed at: " . date('Y-m-d H:i:s') . "</p>";
?>
