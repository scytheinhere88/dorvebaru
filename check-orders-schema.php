<?php
require_once __DIR__ . '/config.php';

echo "=== CHECK ORDERS TABLE SCHEMA ===\n\n";

try {
    $stmt = $pdo->query("DESCRIBE orders");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Orders Table Columns:\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-25s %-20s %-10s %-10s\n", "Field", "Type", "Null", "Key");
    echo str_repeat("-", 80) . "\n";

    foreach ($columns as $col) {
        printf("%-25s %-20s %-10s %-10s\n",
            $col['Field'],
            $col['Type'],
            $col['Null'],
            $col['Key']
        );
    }

    echo "\n" . str_repeat("-", 80) . "\n\n";

    // Check which column contains the total amount
    echo "Looking for total amount column...\n";
    $total_columns = ['final_total', 'total', 'grand_total', 'total_amount', 'amount'];
    foreach ($total_columns as $col_name) {
        $found = false;
        foreach ($columns as $col) {
            if ($col['Field'] === $col_name) {
                echo "✅ Found: $col_name ({$col['Type']})\n";
                $found = true;
                break;
            }
        }
        if (!$found) {
            echo "❌ Not found: $col_name\n";
        }
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
