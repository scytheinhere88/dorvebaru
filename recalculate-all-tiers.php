<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/tier-helper.php';

// Check if running from CLI or browser
$isCLI = php_sapi_name() === 'cli';
$nl = $isCLI ? "\n" : "<br>";

if (!$isCLI) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><title>Recalculate Tiers</title>";
    echo "<style>body{font-family:monospace;background:#1a1a1a;color:#0f0;padding:20px;}";
    echo ".success{color:#0f0;}.error{color:#f00;}.info{color:#ff0;}</style></head><body>";
}

echo "=== RECALCULATE ALL USER TIERS ==={$nl}{$nl}";

try {
    $pdo->beginTransaction();

    // Get all users
    $stmt = $pdo->query("SELECT id, email, current_tier, total_topup FROM users ORDER BY id");
    $users = $stmt->fetchAll();

    $updated = 0;
    $unchanged = 0;
    $errors = 0;

    foreach ($users as $user) {
        echo "Processing User ID: {$user['id']} ({$user['email']}){$nl}";
        echo "  Current Tier: {$user['current_tier']}{$nl}";
        echo "  Current Total Topup: Rp " . number_format($user['total_topup'], 0, ',', '.') . "{$nl}";

        // Calculate actual total from completed topups (admin approved)
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

        $actual_total = $total_from_topups + $total_from_orders;

        echo "  Calculated Total:{$nl}";
        echo "    - From Topups: Rp " . number_format($total_from_topups, 0, ',', '.') . "{$nl}";
        echo "    - From Orders (Midtrans): Rp " . number_format($total_from_orders, 0, ',', '.') . "{$nl}";
        echo "    - TOTAL: Rp " . number_format($actual_total, 0, ',', '.') . "{$nl}";

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
        echo "  Correct Tier: {$tier_info['name']} ({$tier_info['icon']}){$nl}";

        // Update if different
        if ($user['current_tier'] !== $correct_tier || $user['total_topup'] != $actual_total) {
            $stmt = $pdo->prepare("
                UPDATE users
                SET current_tier = ?, total_topup = ?
                WHERE id = ?
            ");
            $stmt->execute([$correct_tier, $actual_total, $user['id']]);

            if (!$isCLI) echo "<span class='success'>";
            echo "  ✅ UPDATED!{$nl}";
            if ($user['current_tier'] !== $correct_tier) {
                echo "     Tier: {$user['current_tier']} → {$correct_tier}{$nl}";
            }
            if ($user['total_topup'] != $actual_total) {
                echo "     Total Topup: Rp " . number_format($user['total_topup'], 0, ',', '.') . " → Rp " . number_format($actual_total, 0, ',', '.') . "{$nl}";
            }
            if (!$isCLI) echo "</span>";
            $updated++;
        } else {
            if (!$isCLI) echo "<span class='info'>";
            echo "  ℹ️  Already correct, no update needed{$nl}";
            if (!$isCLI) echo "</span>";
            $unchanged++;
        }

        echo "{$nl}";
    }

    $pdo->commit();

    if (!$isCLI) echo "<div style='margin-top:20px;padding:20px;background:#2a2a2a;border:2px solid #0f0;'>";
    echo "=== SUMMARY ==={$nl}";
    echo "Total Users: " . count($users) . "{$nl}";
    if (!$isCLI) echo "<span class='success'>";
    echo "Updated: $updated{$nl}";
    if (!$isCLI) echo "</span><span class='info'>";
    echo "Unchanged: $unchanged{$nl}";
    if (!$isCLI) echo "</span>";
    echo "Errors: $errors{$nl}";
    echo "{$nl}✅ All tiers recalculated successfully!{$nl}";
    if (!$isCLI) echo "</div>";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (!$isCLI) echo "<span class='error'>";
    echo "❌ ERROR: " . $e->getMessage() . "{$nl}";
    if (!$isCLI) echo "</span>";
    exit(1);
}

if (!$isCLI) {
    echo "</body></html>";
}
