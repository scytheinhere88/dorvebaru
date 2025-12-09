<?php
require_once __DIR__ . '/config.php';

// Check if running from CLI or browser
$isCLI = php_sapi_name() === 'cli';
$nl = $isCLI ? "\n" : "<br>";

if (!$isCLI) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><title>Fix Tier System</title>";
    echo "<style>body{font-family:monospace;background:#1a1a1a;color:#0f0;padding:20px;}";
    echo ".success{color:#0f0;}.error{color:#f00;}.info{color:#ff0;}</style></head><body>";
}

echo "=== FIX TIER SYSTEM COLUMNS ==={$nl}{$nl}";

try {
    // Check if columns exist
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'current_tier'");
    $has_current_tier = $stmt->rowCount() > 0;

    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'total_topup'");
    $has_total_topup = $stmt->rowCount() > 0;

    echo "Current Status:{$nl}";
    echo "- current_tier column: " . ($has_current_tier ? "✅ EXISTS" : "❌ MISSING") . "{$nl}";
    echo "- total_topup column: " . ($has_total_topup ? "✅ EXISTS" : "❌ MISSING") . "{$nl}{$nl}";

    if (!$has_current_tier || !$has_total_topup) {
        if (!$isCLI) echo "<span class='info'>";
        echo "Adding missing columns...{$nl}{$nl}";
        if (!$isCLI) echo "</span>";

        if (!$has_current_tier) {
            echo "Adding current_tier column...{$nl}";
            $pdo->exec("
                ALTER TABLE users
                ADD COLUMN current_tier VARCHAR(20) DEFAULT 'bronze' AFTER wallet_balance
            ");
            if (!$isCLI) echo "<span class='success'>";
            echo "✅ current_tier column added{$nl}";
            if (!$isCLI) echo "</span>";
        }

        if (!$has_total_topup) {
            echo "Adding total_topup column...{$nl}";
            $pdo->exec("
                ALTER TABLE users
                ADD COLUMN total_topup DECIMAL(15,2) DEFAULT 0.00 AFTER current_tier
            ");
            if (!$isCLI) echo "<span class='success'>";
            echo "✅ total_topup column added{$nl}";
            if (!$isCLI) echo "</span>";
        }

        echo "{$nl}=== CALCULATING INITIAL VALUES ==={$nl}{$nl}";

        // Get all users and calculate their total topup
        $stmt = $pdo->query("SELECT id, email FROM users");
        $users = $stmt->fetchAll();

        foreach ($users as $user) {
            echo "Processing: {$user['email']}{$nl}";

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
            echo "  Total: Rp " . number_format($total, 0, ',', '.') . "{$nl}";
            if (!$isCLI) echo "<span class='success'>";
            echo "  Tier: {$tier_emoji[$tier]} " . ucfirst($tier) . "{$nl}{$nl}";
            if (!$isCLI) echo "</span>";
        }

        if (!$isCLI) echo "<div style='margin-top:20px;padding:20px;background:#2a2a2a;border:2px solid #0f0;'>";
        echo "✅ All users updated successfully!{$nl}";
        if (!$isCLI) echo "</div>";
    } else {
        if (!$isCLI) echo "<span class='success'>";
        echo "✅ All columns already exist. No changes needed.{$nl}{$nl}";
        if (!$isCLI) echo "</span><span class='info'>";
        echo "Run <a href='recalculate-all-tiers.php' style='color:#ff0;'>recalculate-all-tiers.php</a> if you need to recalculate tier values.{$nl}";
        if (!$isCLI) echo "</span>";
    }

} catch (Exception $e) {
    if (!$isCLI) echo "<span class='error'>";
    echo "❌ ERROR: " . $e->getMessage() . "{$nl}";
    if (!$isCLI) echo "</span>";
    exit(1);
}

if (!$isCLI) {
    echo "</body></html>";
}
