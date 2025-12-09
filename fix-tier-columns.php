<?php
require_once __DIR__ . '/config.php';

echo "=== FIX TIER SYSTEM COLUMNS ===\n\n";

try {
    // Check if columns exist
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'current_tier'");
    $has_current_tier = $stmt->rowCount() > 0;

    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'total_topup'");
    $has_total_topup = $stmt->rowCount() > 0;

    echo "Current Status:\n";
    echo "- current_tier column: " . ($has_current_tier ? "✅ EXISTS" : "❌ MISSING") . "\n";
    echo "- total_topup column: " . ($has_total_topup ? "✅ EXISTS" : "❌ MISSING") . "\n\n";

    if (!$has_current_tier || !$has_total_topup) {
        echo "Adding missing columns...\n\n";

        if (!$has_current_tier) {
            echo "Adding current_tier column...\n";
            $pdo->exec("
                ALTER TABLE users
                ADD COLUMN current_tier VARCHAR(20) DEFAULT 'bronze' AFTER wallet_balance
            ");
            echo "✅ current_tier column added\n";
        }

        if (!$has_total_topup) {
            echo "Adding total_topup column...\n";
            $pdo->exec("
                ALTER TABLE users
                ADD COLUMN total_topup DECIMAL(15,2) DEFAULT 0.00 AFTER current_tier
            ");
            echo "✅ total_topup column added\n";
        }

        echo "\n=== CALCULATING INITIAL VALUES ===\n\n";

        // Get all users and calculate their total topup
        $stmt = $pdo->query("SELECT id, email FROM users");
        $users = $stmt->fetchAll();

        foreach ($users as $user) {
            echo "Processing: {$user['email']}\n";

            // Calculate total from completed topups
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(amount), 0) as total
                FROM topups
                WHERE user_id = ? AND status = 'completed'
            ");
            $stmt->execute([$user['id']]);
            $total_from_topups = floatval($stmt->fetchColumn());

            // Calculate total from paid orders via Midtrans
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(final_total), 0) as total
                FROM orders
                WHERE user_id = ? AND payment_status = 'paid' AND payment_method = 'midtrans'
            ");
            $stmt->execute([$user['id']]);
            $total_from_orders = floatval($stmt->fetchColumn());

            $total = $total_from_topups + $total_from_orders;

            // Determine tier
            $tier = 'bronze';
            if ($total >= 10000000) {
                $tier = 'platinum';
            } elseif ($total >= 5000000) {
                $tier = 'gold';
            } elseif ($total >= 1000000) {
                $tier = 'silver';
            }

            // Update user
            $stmt = $pdo->prepare("
                UPDATE users
                SET current_tier = ?, total_topup = ?
                WHERE id = ?
            ");
            $stmt->execute([$tier, $total, $user['id']]);

            $tier_emoji = ['bronze' => '🥉', 'silver' => '🥈', 'gold' => '🥇', 'platinum' => '💎'];
            echo "  Total: Rp " . number_format($total, 0, ',', '.') . "\n";
            echo "  Tier: {$tier_emoji[$tier]} " . ucfirst($tier) . "\n\n";
        }

        echo "✅ All users updated successfully!\n";
    } else {
        echo "✅ All columns already exist. No changes needed.\n";
        echo "\nRun recalculate-all-tiers.php if you need to recalculate tier values.\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
