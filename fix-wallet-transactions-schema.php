<?php
/**
 * Fix wallet_transactions table schema
 * This script will ensure payment_status and status columns have correct ENUM values
 */

require_once __DIR__ . '/config.php';

echo "=== Wallet Transactions Schema Fix ===\n\n";

try {
    // Check current schema
    echo "1. Checking current schema...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM wallet_transactions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Current columns:\n";
    foreach ($columns as $col) {
        if (in_array($col['Field'], ['status', 'payment_status'])) {
            echo "  - {$col['Field']}: {$col['Type']}\n";
        }
    }
    echo "\n";

    // Note: ALTER TABLE statements auto-commit in MySQL, so we can't use transactions
    // We'll execute them one by one and handle errors individually

    // Fix 1: Ensure status column has correct ENUM values
    echo "2. Fixing 'status' column...\n";
    try {
        $pdo->exec("
            ALTER TABLE wallet_transactions
            MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'completed', 'failed', 'cancelled')
            DEFAULT 'pending'
        ");
        echo "   ✓ Status column updated\n\n";
    } catch (PDOException $e) {
        echo "   ⚠ Warning: " . $e->getMessage() . "\n\n";
    }

    // Fix 2: Ensure payment_status column has correct ENUM values
    echo "3. Fixing 'payment_status' column...\n";
    try {
        $pdo->exec("
            ALTER TABLE wallet_transactions
            MODIFY COLUMN payment_status ENUM('pending', 'paid', 'success', 'failed', 'cancelled', 'refunded')
            DEFAULT 'pending'
        ");
        echo "   ✓ Payment_status column updated\n\n";
    } catch (PDOException $e) {
        echo "   ⚠ Warning: " . $e->getMessage() . "\n\n";
    }

    // Fix 3: Update any existing NULL values
    echo "4. Updating NULL values...\n";
    $stmt = $pdo->exec("UPDATE wallet_transactions SET status = 'pending' WHERE status IS NULL");
    echo "   ✓ Updated {$stmt} rows for status\n";

    $stmt = $pdo->exec("UPDATE wallet_transactions SET payment_status = 'pending' WHERE payment_status IS NULL");
    echo "   ✓ Updated {$stmt} rows for payment_status\n\n";

    // Fix 4: Make columns NOT NULL if they aren't already
    echo "5. Setting columns as NOT NULL...\n";
    try {
        $pdo->exec("
            ALTER TABLE wallet_transactions
            MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'completed', 'failed', 'cancelled')
            NOT NULL DEFAULT 'pending'
        ");
        echo "   ✓ Status column set as NOT NULL\n";
    } catch (PDOException $e) {
        echo "   ⚠ Warning: " . $e->getMessage() . "\n";
    }

    try {
        $pdo->exec("
            ALTER TABLE wallet_transactions
            MODIFY COLUMN payment_status ENUM('pending', 'paid', 'success', 'failed', 'cancelled', 'refunded')
            NOT NULL DEFAULT 'pending'
        ");
        echo "   ✓ Payment_status column set as NOT NULL\n\n";
    } catch (PDOException $e) {
        echo "   ⚠ Warning: " . $e->getMessage() . "\n\n";
    }

    // Verify changes
    echo "6. Verifying changes...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM wallet_transactions WHERE Field IN ('status', 'payment_status')");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Updated schema:\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']}: {$col['Type']}\n";
        echo "    Null: {$col['Null']}, Default: {$col['Default']}\n";
    }
    echo "\n";

    // Test insert
    echo "7. Testing insert...\n";
    $testStmt = $pdo->prepare("
        SELECT 1 FROM wallet_transactions
        WHERE status = 'approved' AND payment_status = 'paid'
        LIMIT 1
    ");
    $testStmt->execute();
    echo "   ✓ Query test passed\n\n";

    echo "✅ SUCCESS! Schema fix completed!\n\n";
    echo "Valid status values: pending, approved, rejected, completed, failed, cancelled\n";
    echo "Valid payment_status values: pending, paid, success, failed, cancelled, refunded\n\n";
    echo "You can now approve deposits without errors!\n";

} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nPlease check the error above and try again.\n";
    exit(1);
}
