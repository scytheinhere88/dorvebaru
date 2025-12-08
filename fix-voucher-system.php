<?php
/**
 * Fix Voucher System
 * 1. Create user_vouchers table if not exists
 * 2. Auto-assign tier vouchers to matching users
 */

require_once __DIR__ . '/config.php';

echo "<h1>🎫 Fix Voucher System</h1>";
echo "<style>body{font-family:monospace;padding:20px;background:#f9f9f9;} .success{color:green;font-weight:bold;} .error{color:red;font-weight:bold;} .info{color:blue;} pre{background:#fff;padding:15px;border:1px solid #ddd;} table{border-collapse:collapse;width:100%;margin:20px 0;} th,td{border:1px solid #ddd;padding:10px;text-align:left;} th{background:#f0f0f0;}</style>";

try {
    echo "<h2>1️⃣ Creating user_vouchers table...</h2>";

    $pdo->exec("CREATE TABLE IF NOT EXISTS user_vouchers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        voucher_id INT NOT NULL,
        assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        used_at TIMESTAMP NULL,
        UNIQUE KEY unique_user_voucher (user_id, voucher_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (voucher_id) REFERENCES vouchers(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_voucher_id (voucher_id),
        INDEX idx_used_at (used_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    echo "<p class='success'>✓ user_vouchers table ready!</p>";

    echo "<h2>2️⃣ Auto-assigning tier vouchers to users...</h2>";

    // Get all tier-specific vouchers
    $stmt = $pdo->query("SELECT id, code, name, target_type, target_tier FROM vouchers WHERE target_type = 'tier' AND target_tier IS NOT NULL");
    $tierVouchers = $stmt->fetchAll();

    echo "<p><strong>Found " . count($tierVouchers) . " tier-specific vouchers</strong></p>";

    $totalAssigned = 0;
    foreach ($tierVouchers as $voucher) {
        echo "<h3>Processing: {$voucher['code']} ({$voucher['name']})</h3>";
        echo "<p class='info'>Target Tier: <strong>{$voucher['target_tier']}</strong></p>";

        // Get users with matching tier
        $stmt = $pdo->prepare("SELECT id, email, tier FROM users WHERE tier = ?");
        $stmt->execute([$voucher['target_tier']]);
        $users = $stmt->fetchAll();

        echo "<p>Found <strong>" . count($users) . "</strong> users with tier: {$voucher['target_tier']}</p>";

        $assigned = 0;
        foreach ($users as $user) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO user_vouchers (user_id, voucher_id)
                    VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE assigned_at = CURRENT_TIMESTAMP
                ");
                $stmt->execute([$user['id'], $voucher['id']]);
                $assigned++;
            } catch (Exception $e) {
                // Skip duplicates
            }
        }

        echo "<p class='success'>✓ Assigned to {$assigned} users</p>";
        $totalAssigned += $assigned;
    }

    echo "<h2>3️⃣ Auto-assigning 'all users' vouchers...</h2>";

    // Get all "all users" vouchers
    $stmt = $pdo->query("SELECT id, code, name FROM vouchers WHERE target_type = 'all'");
    $allVouchers = $stmt->fetchAll();

    echo "<p><strong>Found " . count($allVouchers) . " 'all users' vouchers</strong></p>";

    foreach ($allVouchers as $voucher) {
        echo "<h3>Processing: {$voucher['code']} ({$voucher['name']})</h3>";

        // Get ALL users
        $stmt = $pdo->query("SELECT id FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo "<p>Found <strong>" . count($users) . "</strong> users</p>";

        $assigned = 0;
        foreach ($users as $userId) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO user_vouchers (user_id, voucher_id)
                    VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE assigned_at = CURRENT_TIMESTAMP
                ");
                $stmt->execute([$userId, $voucher['id']]);
                $assigned++;
            } catch (Exception $e) {
                // Skip duplicates
            }
        }

        echo "<p class='success'>✓ Assigned to {$assigned} users</p>";
        $totalAssigned += $assigned;
    }

    echo "<h2>4️⃣ Summary Report</h2>";

    // Count total user_vouchers
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM user_vouchers");
    $total = $stmt->fetchColumn();

    echo "<div style='background:white;padding:20px;border-radius:8px;'>";
    echo "<p class='success'>✅ Total assignments: <strong>{$total}</strong></p>";
    echo "<p class='info'>New assignments this run: <strong>{$totalAssigned}</strong></p>";
    echo "</div>";

    echo "<h3>📊 User Vouchers by Tier:</h3>";

    $stmt = $pdo->query("
        SELECT
            u.tier,
            COUNT(DISTINCT uv.id) as voucher_count,
            COUNT(DISTINCT u.id) as user_count
        FROM users u
        LEFT JOIN user_vouchers uv ON u.id = uv.user_id
        GROUP BY u.tier
        ORDER BY
            CASE u.tier
                WHEN 'vvip' THEN 5
                WHEN 'platinum' THEN 4
                WHEN 'gold' THEN 3
                WHEN 'silver' THEN 2
                WHEN 'bronze' THEN 1
                ELSE 0
            END DESC
    ");
    $tierStats = $stmt->fetchAll();

    echo "<table>";
    echo "<tr><th>Tier</th><th>Users</th><th>Total Vouchers Assigned</th></tr>";
    foreach ($tierStats as $stat) {
        $tierIcon = ['bronze' => '🥉', 'silver' => '🥈', 'gold' => '🥇', 'platinum' => '💎', 'vvip' => '👑'][$stat['tier']] ?? '👤';
        echo "<tr>";
        echo "<td>{$tierIcon} " . strtoupper($stat['tier']) . "</td>";
        echo "<td>{$stat['user_count']}</td>";
        echo "<td><strong>{$stat['voucher_count']}</strong></td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<h3>📋 Sample User Vouchers:</h3>";

    $stmt = $pdo->query("
        SELECT
            u.email,
            u.tier,
            v.code,
            v.name,
            uv.assigned_at
        FROM user_vouchers uv
        JOIN users u ON uv.user_id = u.id
        JOIN vouchers v ON uv.voucher_id = v.id
        ORDER BY uv.assigned_at DESC
        LIMIT 10
    ");
    $samples = $stmt->fetchAll();

    echo "<table>";
    echo "<tr><th>User Email</th><th>Tier</th><th>Voucher Code</th><th>Voucher Name</th><th>Assigned At</th></tr>";
    foreach ($samples as $sample) {
        echo "<tr>";
        echo "<td>{$sample['email']}</td>";
        echo "<td>{$sample['tier']}</td>";
        echo "<td><strong>{$sample['code']}</strong></td>";
        echo "<td>{$sample['name']}</td>";
        echo "<td>{$sample['assigned_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<div style='background:linear-gradient(135deg,#10B981 0%,#059669 100%);color:white;padding:30px;border-radius:8px;text-align:center;margin-top:40px;'>";
    echo "<h2 style='color:white;margin:0;'>✅ VOUCHER SYSTEM FIXED!</h2>";
    echo "<p style='margin:10px 0 0;'>All tier vouchers have been assigned to matching users</p>";
    echo "</div>";

    echo "<div style='margin-top:30px;text-align:center;'>";
    echo "<a href='/admin/vouchers/' style='display:inline-block;padding:16px 32px;background:#3B82F6;color:white;text-decoration:none;border-radius:8px;font-weight:bold;'>Go to Vouchers</a>";
    echo "<a href='/member/vouchers/' style='display:inline-block;padding:16px 32px;background:#10B981;color:white;text-decoration:none;border-radius:8px;font-weight:bold;margin-left:12px;'>View as Member</a>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='background:#FEE2E2;color:#DC2626;padding:20px;border-radius:8px;'>";
    echo "<h2>❌ ERROR!</h2>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

echo "<hr style='margin:40px 0;'>";
echo "<p style='text-align:center;color:#666;'>Completed at: " . date('Y-m-d H:i:s') . "</p>";
?>
