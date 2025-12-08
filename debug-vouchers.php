<?php
/**
 * DEBUG VOUCHERS - Find why vouchers not showing
 */

require_once __DIR__ . '/config.php';

if (!isLoggedIn()) {
    die('Login first to test vouchers!');
}

$userId = $_SESSION['user_id'];
$user = getCurrentUser();

echo "<h1>🔍 DEBUG: Voucher System</h1>";
echo "<style>body{font-family:monospace;padding:20px;background:#f9f9f9;} .success{color:green;font-weight:bold;} .error{color:red;font-weight:bold;} .info{color:blue;} pre{background:#fff;padding:15px;border:1px solid #ddd;margin:10px 0;overflow:auto;} table{border-collapse:collapse;width:100%;margin:20px 0;background:white;} th,td{border:1px solid #ddd;padding:12px;text-align:left;} th{background:#f0f0f0;font-weight:bold;} .box{background:white;padding:20px;margin:20px 0;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);}</style>";

echo "<div class='box'>";
echo "<h2>👤 Current User</h2>";
echo "<table>";
echo "<tr><th>Field</th><th>Value</th></tr>";
echo "<tr><td><strong>User ID</strong></td><td>" . $userId . "</td></tr>";
echo "<tr><td><strong>Email</strong></td><td>" . htmlspecialchars($user['email']) . "</td></tr>";
echo "<tr><td><strong>Name</strong></td><td>" . htmlspecialchars($user['name'] ?? 'N/A') . "</td></tr>";
echo "<tr><td><strong>Tier</strong></td><td><strong>" . strtoupper($user['tier'] ?? 'none') . "</strong></td></tr>";
echo "</table>";
echo "</div>";

echo "<div class='box'>";
echo "<h2>1️⃣ Check user_vouchers Table</h2>";

try {
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_vouchers'");
    $tableExists = $stmt->fetch();

    if (!$tableExists) {
        echo "<p class='error'>❌ Table 'user_vouchers' DOES NOT EXIST!</p>";
        echo "<p>Run <a href='/fix-voucher-system.php'>fix-voucher-system.php</a> to create it.</p>";
    } else {
        echo "<p class='success'>✅ Table 'user_vouchers' exists</p>";

        // Count total records
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM user_vouchers");
        $totalCount = $stmt->fetch()['total'];
        echo "<p>Total assignments in table: <strong>{$totalCount}</strong></p>";

        // Count for this user
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM user_vouchers WHERE user_id = ?");
        $stmt->execute([$userId]);
        $userCount = $stmt->fetch()['total'];
        echo "<p>Assignments for YOU: <strong class='".($userCount > 0 ? 'success' : 'error')."'>{$userCount}</strong></p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

echo "<div class='box'>";
echo "<h2>2️⃣ Your Assigned Vouchers</h2>";

try {
    $stmt = $pdo->prepare("
        SELECT v.*, uv.assigned_at
        FROM user_vouchers uv
        JOIN vouchers v ON uv.voucher_id = v.id
        WHERE uv.user_id = ?
        ORDER BY uv.assigned_at DESC
    ");
    $stmt->execute([$userId]);
    $userVouchers = $stmt->fetchAll();

    if (empty($userVouchers)) {
        echo "<p class='error'>❌ NO VOUCHERS ASSIGNED TO YOU!</p>";
        echo "<p>This means either:</p>";
        echo "<ul>";
        echo "<li>You don't have vouchers assigned in user_vouchers table</li>";
        echo "<li>Your tier doesn't match any tier-specific vouchers</li>";
        echo "<li>No 'all users' vouchers exist</li>";
        echo "</ul>";
    } else {
        echo "<table>";
        echo "<tr><th>Code</th><th>Name</th><th>Type</th><th>Value</th><th>Target</th><th>Valid Until</th><th>Active</th><th>Assigned At</th></tr>";

        foreach ($userVouchers as $voucher) {
            $isActive = ($voucher['is_active'] == 1);
            $isValid = (strtotime($voucher['valid_until']) >= time());
            $isValidFrom = (strtotime($voucher['valid_from']) <= time());
            $canUse = $isActive && $isValid && $isValidFrom;

            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($voucher['code']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($voucher['name']) . "</td>";
            echo "<td>" . $voucher['type'] . "</td>";

            if ($voucher['type'] === 'free_shipping') {
                echo "<td>FREE SHIP</td>";
            } elseif ($voucher['discount_type'] === 'percentage') {
                echo "<td>{$voucher['discount_value']}%</td>";
            } else {
                echo "<td>Rp " . number_format($voucher['discount_value'], 0, ',', '.') . "</td>";
            }

            echo "<td>" . $voucher['target_type'] . ($voucher['target_tier'] ? " ({$voucher['target_tier']})" : '') . "</td>";
            echo "<td>" . date('d M Y', strtotime($voucher['valid_until'])) . "</td>";
            echo "<td class='".($canUse ? 'success' : 'error')."'>" . ($canUse ? '✅ YES' : '❌ NO') . "</td>";
            echo "<td>" . date('d M Y H:i', strtotime($voucher['assigned_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

echo "<div class='box'>";
echo "<h2>3️⃣ Current Query (OLD - WRONG)</h2>";

echo "<pre>SELECT v.*,
    COALESCE(uv.usage_count, 0) as usage_count
FROM vouchers v
LEFT JOIN user_vouchers uv ON v.id = uv.voucher_id AND uv.user_id = ?
WHERE v.is_active = 1
  AND v.valid_from <= NOW()
  AND v.valid_until >= NOW()</pre>";

echo "<p class='error'>❌ <strong>PROBLEM:</strong> LEFT JOIN shows ALL vouchers, not just assigned ones!</p>";

try {
    $stmt = $pdo->prepare("
        SELECT v.*,
               COALESCE(uv.usage_count, 0) as usage_count,
               CASE
                   WHEN v.total_usage_limit IS NOT NULL AND v.total_used >= v.total_usage_limit THEN 1
                   ELSE 0
               END as is_limit_reached
        FROM vouchers v
        LEFT JOIN user_vouchers uv ON v.id = uv.voucher_id AND uv.user_id = ?
        WHERE v.is_active = 1
          AND v.valid_from <= NOW()
          AND v.valid_until >= NOW()
        ORDER BY v.type DESC, v.discount_value DESC
    ");
    $stmt->execute([$userId]);
    $oldQueryResults = $stmt->fetchAll();

    echo "<p>Old query returns: <strong>" . count($oldQueryResults) . " vouchers</strong></p>";

    if (!empty($oldQueryResults)) {
        echo "<table>";
        echo "<tr><th>Code</th><th>Name</th><th>Type</th><th>In user_vouchers?</th></tr>";

        foreach ($oldQueryResults as $voucher) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM user_vouchers WHERE voucher_id = ? AND user_id = ?");
            $stmt->execute([$voucher['id'], $userId]);
            $isAssigned = $stmt->fetch()['cnt'] > 0;

            echo "<tr>";
            echo "<td>" . htmlspecialchars($voucher['code']) . "</td>";
            echo "<td>" . htmlspecialchars($voucher['name']) . "</td>";
            echo "<td>" . $voucher['type'] . "</td>";
            echo "<td class='".($isAssigned ? 'success' : 'error')."'>" . ($isAssigned ? '✅ YES' : '❌ NO') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

echo "<div class='box'>";
echo "<h2>4️⃣ New Query (FIXED - CORRECT)</h2>";

echo "<pre>SELECT v.*,
    uv.assigned_at,
    COALESCE(
        (SELECT COUNT(*) FROM orders
         WHERE user_id = ? AND voucher_code = v.code),
        0
    ) as usage_count
FROM user_vouchers uv
INNER JOIN vouchers v ON uv.voucher_id = v.id
WHERE uv.user_id = ?
  AND v.is_active = 1
  AND v.valid_from <= NOW()
  AND v.valid_until >= NOW()</pre>";

echo "<p class='success'>✅ <strong>FIX:</strong> INNER JOIN only shows vouchers in user_vouchers table!</p>";

try {
    $stmt = $pdo->prepare("
        SELECT v.*,
               uv.assigned_at,
               0 as usage_count
        FROM user_vouchers uv
        INNER JOIN vouchers v ON uv.voucher_id = v.id
        WHERE uv.user_id = ?
          AND v.is_active = 1
          AND v.valid_from <= NOW()
          AND v.valid_until >= NOW()
        ORDER BY v.type DESC, v.discount_value DESC
    ");
    $stmt->execute([$userId]);
    $newQueryResults = $stmt->fetchAll();

    echo "<p>New query returns: <strong class='".(!empty($newQueryResults) ? 'success' : 'error')."'>" . count($newQueryResults) . " vouchers</strong></p>";

    if (!empty($newQueryResults)) {
        echo "<table>";
        echo "<tr><th>Code</th><th>Name</th><th>Type</th><th>Value</th><th>Assigned</th><th>Used</th></tr>";

        foreach ($newQueryResults as $voucher) {
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($voucher['code']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($voucher['name']) . "</td>";
            echo "<td>" . $voucher['type'] . "</td>";

            if ($voucher['type'] === 'free_shipping') {
                echo "<td>FREE SHIP</td>";
            } elseif ($voucher['discount_type'] === 'percentage') {
                echo "<td>{$voucher['discount_value']}%</td>";
            } else {
                echo "<td>Rp " . number_format($voucher['discount_value'], 0, ',', '.') . "</td>";
            }

            echo "<td>" . date('d M Y', strtotime($voucher['assigned_at'])) . "</td>";
            echo "<td>{$voucher['usage_count']} times</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>❌ Still no vouchers! Check if vouchers are actually assigned to your user ID.</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

echo "<div class='box' style='background:#FFF3CD;border-left:4px solid #FFC107;'>";
echo "<h2>💡 Summary & Fix</h2>";
echo "<table>";
echo "<tr><th>Issue</th><th>Status</th><th>Fix</th></tr>";
echo "<tr>";
echo "<td><strong>user_vouchers table</strong></td>";
echo "<td>" . (isset($tableExists) && $tableExists ? '<span class="success">✅ Exists</span>' : '<span class="error">❌ Missing</span>') . "</td>";
echo "<td>" . (!isset($tableExists) || !$tableExists ? 'Run fix-voucher-system.php' : 'OK') . "</td>";
echo "</tr>";
echo "<tr>";
echo "<td><strong>Vouchers assigned to you</strong></td>";
echo "<td>" . (isset($userCount) && $userCount > 0 ? '<span class="success">✅ ' . $userCount . ' vouchers</span>' : '<span class="error">❌ None</span>') . "</td>";
echo "<td>" . (isset($userCount) && $userCount == 0 ? 'Run fix-voucher-system.php to auto-assign' : 'OK') . "</td>";
echo "</tr>";
echo "<tr>";
echo "<td><strong>Query logic</strong></td>";
echo "<td><span class='error'>❌ Using LEFT JOIN</span></td>";
echo "<td><strong>Change to INNER JOIN</strong></td>";
echo "</tr>";
echo "</table>";
echo "</div>";

echo "<div class='box' style='background:#E3F2FD;border-left:4px solid #2196F3;'>";
echo "<h2>🔧 Quick Actions</h2>";
echo "<div style='display:flex;gap:12px;flex-wrap:wrap;margin-top:16px;'>";
echo "<a href='/fix-voucher-system.php' style='padding:12px 24px;background:#3B82F6;color:white;text-decoration:none;border-radius:6px;font-weight:bold;'>🔄 Re-run Voucher Fix</a>";
echo "<a href='/member/vouchers/' style='padding:12px 24px;background:#10B981;color:white;text-decoration:none;border-radius:6px;font-weight:bold;'>👀 View My Vouchers Page</a>";
echo "<a href='/admin/vouchers/' style='padding:12px 24px;background:#8B5CF6;color:white;text-decoration:none;border-radius:6px;font-weight:bold;'>⚙️ Admin Vouchers</a>";
echo "</div>";
echo "</div>";

echo "<hr style='margin:40px 0;'>";
echo "<p style='text-align:center;color:#666;'>Debug completed at: " . date('Y-m-d H:i:s') . "</p>";
?>
