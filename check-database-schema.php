<?php
/**
 * CHECK DATABASE SCHEMA
 * Find missing columns causing errors
 */

require_once __DIR__ . '/config.php';

echo "<h1>🔍 DATABASE SCHEMA CHECK</h1>";
echo "<style>body{font-family:monospace;padding:20px;background:#f9f9f9;} .success{color:green;font-weight:bold;} .error{color:red;font-weight:bold;} table{border-collapse:collapse;width:100%;margin:20px 0;background:white;} th,td{border:1px solid #ddd;padding:12px;text-align:left;} th{background:#f0f0f0;font-weight:bold;} .box{background:white;padding:20px;margin:20px 0;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);}</style>";

function checkTable($pdo, $tableName) {
    echo "<div class='box'>";
    echo "<h2>📋 Table: {$tableName}</h2>";

    try {
        $stmt = $pdo->query("DESCRIBE {$tableName}");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<table>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
            echo "</tr>";
        }

        echo "</table>";
        echo "<p class='success'>✅ Total columns: " . count($columns) . "</p>";

        return $columns;

    } catch (Exception $e) {
        echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
        return [];
    }

    echo "</div>";
}

// Check key tables
echo "<h2>🔍 Checking Tables Causing Errors:</h2>";

echo "<div class='box' style='background:#FFF3CD;border-left:4px solid #F59E0B;'>";
echo "<h3>❌ Errors Found:</h3>";
echo "<ol>";
echo "<li><strong>user_vouchers:</strong> Column 'used_at' not found</li>";
echo "<li><strong>orders:</strong> Column 'voucher_code' not found</li>";
echo "<li><strong>products:</strong> Column 'weight' not found</li>";
echo "</ol>";
echo "</div>";

// 1. Check user_vouchers
$userVouchersColumns = checkTable($pdo, 'user_vouchers');
$hasUsedAt = false;
foreach ($userVouchersColumns as $col) {
    if ($col['Field'] === 'used_at') {
        $hasUsedAt = true;
        break;
    }
}
echo "<div class='box'>";
echo "<p><strong>Has 'used_at' column?</strong> " . ($hasUsedAt ? '<span class="success">✅ YES</span>' : '<span class="error">❌ NO</span>') . "</p>";
echo "</div>";

// 2. Check orders
$ordersColumns = checkTable($pdo, 'orders');
$hasVoucherCode = false;
foreach ($ordersColumns as $col) {
    if ($col['Field'] === 'voucher_code') {
        $hasVoucherCode = true;
        break;
    }
}
echo "<div class='box'>";
echo "<p><strong>Has 'voucher_code' column?</strong> " . ($hasVoucherCode ? '<span class="success">✅ YES</span>' : '<span class="error">❌ NO</span>') . "</p>";
echo "</div>";

// 3. Check products
$productsColumns = checkTable($pdo, 'products');
$hasWeight = false;
foreach ($productsColumns as $col) {
    if ($col['Field'] === 'weight') {
        $hasWeight = true;
        break;
    }
}
echo "<div class='box'>";
echo "<p><strong>Has 'weight' column?</strong> " . ($hasWeight ? '<span class="success">✅ YES</span>' : '<span class="error">❌ NO</span>') . "</p>";
echo "</div>";

// Summary
echo "<div class='box' style='background:#E3F2FD;border-left:4px solid #2196F3;'>";
echo "<h2>💡 Summary</h2>";
echo "<table>";
echo "<tr><th>Table</th><th>Missing Column</th><th>Status</th></tr>";
echo "<tr>";
echo "<td><strong>user_vouchers</strong></td>";
echo "<td>used_at</td>";
echo "<td>" . ($hasUsedAt ? '<span class="success">✅ EXISTS</span>' : '<span class="error">❌ MISSING</span>') . "</td>";
echo "</tr>";
echo "<tr>";
echo "<td><strong>orders</strong></td>";
echo "<td>voucher_code</td>";
echo "<td>" . ($hasVoucherCode ? '<span class="success">✅ EXISTS</span>' : '<span class="error">❌ MISSING</span>') . "</td>";
echo "</tr>";
echo "<tr>";
echo "<td><strong>products</strong></td>";
echo "<td>weight</td>";
echo "<td>" . ($hasWeight ? '<span class="success">✅ EXISTS</span>' : '<span class="error">❌ MISSING</span>') . "</td>";
echo "</tr>";
echo "</table>";
echo "</div>";

// Check product_variants (weight might be there)
echo "<h2>🔍 Checking product_variants (weight alternative):</h2>";
$variantsColumns = checkTable($pdo, 'product_variants');
$variantHasWeight = false;
foreach ($variantsColumns as $col) {
    if ($col['Field'] === 'weight') {
        $variantHasWeight = true;
        break;
    }
}
echo "<div class='box'>";
echo "<p><strong>product_variants has 'weight' column?</strong> " . ($variantHasWeight ? '<span class="success">✅ YES</span>' : '<span class="error">❌ NO</span>') . "</p>";
echo "</div>";

echo "<hr style='margin:40px 0;'>";
echo "<p style='text-align:center;color:#666;'>Check completed at: " . date('Y-m-d H:i:s') . "</p>";
?>
