<?php
require_once __DIR__ . '/config.php';

echo "<h1>🔍 Checking Voucher System</h1>";
echo "<style>body{font-family:monospace;padding:20px;} .success{color:green;} .error{color:red;}</style>";

// Check user_vouchers table
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_vouchers'");
    if ($stmt->rowCount() > 0) {
        echo "<p class='success'>✓ user_vouchers table exists</p>";

        // Check structure
        $stmt = $pdo->query("DESCRIBE user_vouchers");
        $columns = $stmt->fetchAll();
        echo "<h3>Columns:</h3><ul>";
        foreach ($columns as $col) {
            echo "<li>{$col['Field']} - {$col['Type']}</li>";
        }
        echo "</ul>";

        // Count entries
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM user_vouchers");
        $count = $stmt->fetch();
        echo "<p><strong>Total user_vouchers: {$count['total']}</strong></p>";

    } else {
        echo "<p class='error'>✗ user_vouchers table NOT FOUND!</p>";
        echo "<p>Creating table...</p>";

        $pdo->exec("CREATE TABLE IF NOT EXISTS user_vouchers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            voucher_id INT NOT NULL,
            assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            used_at TIMESTAMP NULL,
            UNIQUE KEY unique_user_voucher (user_id, voucher_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (voucher_id) REFERENCES vouchers(id) ON DELETE CASCADE
        )");

        echo "<p class='success'>✓ user_vouchers table created!</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

// Check vouchers table
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM vouchers");
    $count = $stmt->fetch();
    echo "<h3>Vouchers:</h3>";
    echo "<p><strong>Total vouchers: {$count['total']}</strong></p>";

    $stmt = $pdo->query("SELECT id, code, name, target_type, target_tier FROM vouchers LIMIT 5");
    $vouchers = $stmt->fetchAll();
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Code</th><th>Name</th><th>Target Type</th><th>Target Tier</th></tr>";
    foreach ($vouchers as $v) {
        echo "<tr>";
        echo "<td>{$v['id']}</td>";
        echo "<td>{$v['code']}</td>";
        echo "<td>{$v['name']}</td>";
        echo "<td>{$v['target_type']}</td>";
        echo "<td>{$v['target_tier']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

// Check users with tiers
try {
    $stmt = $pdo->query("SELECT tier, COUNT(*) as count FROM users GROUP BY tier");
    $tiers = $stmt->fetchAll();
    echo "<h3>Users by Tier:</h3>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Tier</th><th>Count</th></tr>";
    foreach ($tiers as $t) {
        echo "<tr><td>{$t['tier']}</td><td>{$t['count']}</td></tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr><p><a href='/admin/vouchers/'>Go to Vouchers</a></p>";
?>
