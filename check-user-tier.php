<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/tier-helper.php';

// Check if running from CLI or browser
$isCLI = php_sapi_name() === 'cli';
$nl = $isCLI ? "\n" : "<br>";

if (!$isCLI) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><title>Check User Tier</title>";
    echo "<style>body{font-family:monospace;background:#1a1a1a;color:#0f0;padding:20px;}";
    echo ".success{color:#0f0;}.error{color:#f00;}.info{color:#ff0;}.warn{color:#ffa500;}</style></head><body>";
}

echo "=== CHECK USER TIER DETAILS ==={$nl}{$nl}";

try {
    // Get user ID from session or parameter
    session_start();
    $userId = $_SESSION['user_id'] ?? ($_GET['user_id'] ?? null);

    if (!$userId) {
        if (!$isCLI) echo "<span class='error'>";
        echo "❌ No user ID provided. Please login or pass ?user_id=X{$nl}";
        if (!$isCLI) echo "</span>";
        exit(1);
    }

    // Get user info
    $stmt = $pdo->prepare("SELECT id, email, current_tier, total_topup, wallet_balance FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        if (!$isCLI) echo "<span class='error'>";
        echo "❌ User not found{$nl}";
        if (!$isCLI) echo "</span>";
        exit(1);
    }

    echo "User Information:{$nl}";
    echo "================={$nl}";
    echo "ID: {$user['id']}{$nl}";
    echo "Email: {$user['email']}{$nl}";
    if (!$isCLI) echo "<span class='warn'>";
    echo "Current Tier: " . strtoupper($user['current_tier']) . "{$nl}";
    echo "Total Topup (DB): Rp " . number_format($user['total_topup'], 0, ',', '.') . "{$nl}";
    echo "Wallet Balance: Rp " . number_format($user['wallet_balance'], 0, ',', '.') . "{$nl}{$nl}";
    if (!$isCLI) echo "</span>";

    // Calculate actual total from completed topups
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) as total
        FROM wallet_topups
        WHERE user_id = ? AND payment_status = 'paid'
    ");
    $stmt->execute([$userId]);
    $total_from_topups = floatval($stmt->fetchColumn());

    // Calculate total from paid orders via Midtrans
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as total
        FROM orders
        WHERE user_id = ? AND payment_status = 'paid' AND payment_method = 'midtrans'
    ");
    $stmt->execute([$userId]);
    $total_from_orders = floatval($stmt->fetchColumn());

    $actual_total = $total_from_topups + $total_from_orders;

    echo "Calculated Totals:{$nl}";
    echo "================={$nl}";
    if (!$isCLI) echo "<span class='info'>";
    echo "From Wallet Topups: Rp " . number_format($total_from_topups, 0, ',', '.') . "{$nl}";
    echo "From Orders (Midtrans): Rp " . number_format($total_from_orders, 0, ',', '.') . "{$nl}";
    echo "ACTUAL TOTAL: Rp " . number_format($actual_total, 0, ',', '.') . "{$nl}{$nl}";
    if (!$isCLI) echo "</span>";

    // Determine correct tier
    $correct_tier = 'bronze';
    if ($actual_total >= 10000000) {
        $correct_tier = 'platinum';
    } elseif ($actual_total >= 5000000) {
        $correct_tier = 'gold';
    } elseif ($actual_total >= 1000000) {
        $correct_tier = 'silver';
    }

    $tier_info = getTierInfo($correct_tier);
    echo "Correct Tier Should Be:{$nl}";
    echo "======================{$nl}";
    if (!$isCLI) echo "<span class='success'>";
    echo "{$tier_info['icon']} {$tier_info['name']}{$nl}";
    echo "Discount: {$tier_info['discount']}%{$nl}";
    echo "Commission: {$tier_info['commission']}%{$nl}{$nl}";
    if (!$isCLI) echo "</span>";

    // Check if needs update
    if ($user['current_tier'] !== $correct_tier || $user['total_topup'] != $actual_total) {
        if (!$isCLI) echo "<span class='warn'>";
        echo "⚠️  MISMATCH DETECTED!{$nl}{$nl}";
        if (!$isCLI) echo "</span>";

        if ($user['current_tier'] !== $correct_tier) {
            echo "Tier: {$user['current_tier']} → {$correct_tier}{$nl}";
        }
        if ($user['total_topup'] != $actual_total) {
            echo "Total Topup: Rp " . number_format($user['total_topup'], 0, ',', '.') . " → Rp " . number_format($actual_total, 0, ',', '.') . "{$nl}";
        }

        echo "{$nl}Updating user...{$nl}";

        $stmt = $pdo->prepare("
            UPDATE users
            SET current_tier = ?, total_topup = ?
            WHERE id = ?
        ");
        $stmt->execute([$correct_tier, $actual_total, $userId]);

        if (!$isCLI) echo "<span class='success'>";
        echo "✅ USER UPDATED SUCCESSFULLY!{$nl}";
        if (!$isCLI) echo "</span>";
    } else {
        if (!$isCLI) echo "<span class='success'>";
        echo "✅ Everything is correct! No update needed.{$nl}";
        if (!$isCLI) echo "</span>";
    }

    echo "{$nl}";
    if (!$isCLI) {
        echo "<div style='margin-top:20px;padding:15px;background:#2a2a2a;border:2px solid #0f0;'>";
        echo "<a href='recalculate-all-tiers.php' style='color:#0f0;'>Recalculate All Users</a> | ";
        echo "<a href='member/dashboard.php' style='color:#0f0;'>Go to Dashboard</a>";
        echo "</div>";
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
