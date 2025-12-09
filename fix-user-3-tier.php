<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/tier-helper.php';

$isCLI = php_sapi_name() === 'cli';
$nl = $isCLI ? "\n" : "<br>";

if (!$isCLI) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><title>Fix User 3 Tier</title>";
    echo "<style>body{font-family:monospace;background:#1a1a1a;color:#0f0;padding:20px;}";
    echo ".success{color:#0f0;}.error{color:#f00;}.warn{color:#ffa500;}</style></head><body>";
}

echo "=== FIX USER ID 3 TIER ==={$nl}{$nl}";

try {
    $userId = 3;

    // Get current user data
    $stmt = $pdo->prepare("SELECT id, email, wallet_balance, total_topup, current_tier FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        echo "<span class='error'>❌ User not found!{$nl}</span>";
        exit;
    }

    echo "Current Status:{$nl}";
    echo "=============={$nl}";
    echo "Email: {$user['email']}{$nl}";
    echo "Wallet Balance: Rp " . number_format($user['wallet_balance'], 0, ',', '.') . "{$nl}";
    echo "Total Topup: Rp " . number_format($user['total_topup'], 0, ',', '.') . "{$nl}";
    echo "Current Tier: " . strtoupper($user['current_tier']) . "{$nl}{$nl}";

    // Calculate actual total from approved topups
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) as total
        FROM topups
        WHERE user_id = ? AND status = 'completed'
    ");
    $stmt->execute([$userId]);
    $total_from_approved = floatval($stmt->fetchColumn());

    echo "Total from Approved Topups: Rp " . number_format($total_from_approved, 0, ',', '.') . "{$nl}{$nl}";

    // If no approved topups but wallet has balance, use wallet balance as total_topup
    $new_total_topup = $total_from_approved > 0 ? $total_from_approved : $user['wallet_balance'];

    echo "New Total Topup will be: Rp " . number_format($new_total_topup, 0, ',', '.') . "{$nl}{$nl}";

    // Determine correct tier
    $new_tier = 'bronze';
    if ($new_total_topup >= 10000000) {
        $new_tier = 'platinum';
    } elseif ($new_total_topup >= 5000000) {
        $new_tier = 'gold';
    } elseif ($new_total_topup >= 1000000) {
        $new_tier = 'silver';
    }

    $tier_info = getTierInfo($new_tier);
    echo "New Tier will be: {$tier_info['icon']} {$tier_info['name']}{$nl}";
    echo "- Discount: {$tier_info['discount']}%{$nl}";
    echo "- Commission: {$tier_info['commission']}%{$nl}{$nl}";

    // Update user
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        UPDATE users
        SET total_topup = ?, current_tier = ?
        WHERE id = ?
    ");
    $stmt->execute([$new_total_topup, $new_tier, $userId]);

    $pdo->commit();

    echo "<span class='success'>✅ USER UPDATED SUCCESSFULLY!{$nl}{$nl}</span>";

    // Verify update
    $stmt = $pdo->prepare("SELECT wallet_balance, total_topup, current_tier FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $updated_user = $stmt->fetch();

    echo "Updated Status:{$nl}";
    echo "==============={$nl}";
    echo "Wallet Balance: Rp " . number_format($updated_user['wallet_balance'], 0, ',', '.') . "{$nl}";
    echo "Total Topup: Rp " . number_format($updated_user['total_topup'], 0, ',', '.') . "{$nl}";
    echo "Current Tier: " . strtoupper($updated_user['current_tier']) . "{$nl}{$nl}";

    $tier_info = getTierInfo($updated_user['current_tier']);
    echo "<span class='success'>";
    echo "🎉 TIER UPGRADE COMPLETE!{$nl}";
    echo "You are now: {$tier_info['icon']} {$tier_info['name']}{$nl}";
    echo "Member Discount: {$tier_info['discount']}%{$nl}";
    echo "Referral Commission: {$tier_info['commission']}%{$nl}";
    echo "</span>{$nl}";

    if (!$isCLI) {
        echo "<div style='margin-top:20px;padding:15px;background:#2a2a2a;border:2px solid #0f0;'>";
        echo "<a href='member/dashboard.php' style='color:#0f0;'>Go to Dashboard</a>";
        echo "</div>";
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<span class='error'>❌ ERROR: " . $e->getMessage() . "{$nl}</span>";
    exit(1);
}

if (!$isCLI) {
    echo "</body></html>";
}
