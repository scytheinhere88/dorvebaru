<?php
require_once __DIR__ . '/config.php';

$userId = $_GET['user_id'] ?? 3;
$isCLI = php_sapi_name() === 'cli';
$nl = $isCLI ? "\n" : "<br>";

if (!$isCLI) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><title>Check Topup Records</title>";
    echo "<style>body{font-family:monospace;background:#1a1a1a;color:#0f0;padding:20px;}";
    echo "table{border-collapse:collapse;margin:20px 0;}th,td{border:1px solid #0f0;padding:8px;text-align:left;}";
    echo ".error{color:#f00;}.success{color:#0f0;}.warn{color:#ffa500;}</style></head><body>";
}

echo "=== TOPUP RECORDS FOR USER ID: {$userId} ==={$nl}{$nl}";

try {
    // Check user
    $stmt = $pdo->prepare("SELECT id, email, wallet_balance, total_topup FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        echo "<span class='error'>User not found!</span>{$nl}";
        exit;
    }

    echo "User: {$user['email']}{$nl}";
    echo "Wallet Balance: Rp " . number_format($user['wallet_balance'], 0, ',', '.') . "{$nl}";
    echo "Total Topup (DB): Rp " . number_format($user['total_topup'], 0, ',', '.') . "{$nl}{$nl}";

    // Check wallet_topups table
    echo "=== WALLET TOPUPS TABLE ==={$nl}";
    $stmt = $pdo->prepare("
        SELECT id, amount, payment_method, payment_status, created_at, updated_at
        FROM wallet_topups
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);
    $topups = $stmt->fetchAll();

    if (empty($topups)) {
        echo "<span class='warn'>❌ NO TOPUP RECORDS FOUND{$nl}{$nl}</span>";
    } else {
        if (!$isCLI) {
            echo "<table><tr><th>ID</th><th>Amount</th><th>Method</th><th>Status</th><th>Created</th></tr>";
            foreach ($topups as $t) {
                $statusClass = $t['payment_status'] === 'paid' ? 'success' : 'warn';
                echo "<tr><td>{$t['id']}</td>";
                echo "<td>Rp " . number_format($t['amount'], 0, ',', '.') . "</td>";
                echo "<td>{$t['payment_method']}</td>";
                echo "<td class='{$statusClass}'>{$t['payment_status']}</td>";
                echo "<td>{$t['created_at']}</td></tr>";
            }
            echo "</table>";
        } else {
            foreach ($topups as $t) {
                echo "ID: {$t['id']}, Amount: Rp " . number_format($t['amount'], 0, ',', '.') .
                     ", Status: {$t['payment_status']}, Created: {$t['created_at']}{$nl}";
            }
        }

        $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM wallet_topups WHERE user_id = ? AND payment_status = 'paid'");
        $stmt->execute([$userId]);
        $paidTotal = floatval($stmt->fetchColumn());
        echo "{$nl}Total PAID Topups: Rp " . number_format($paidTotal, 0, ',', '.') . "{$nl}{$nl}";
    }

    // Check wallet_transactions table
    echo "=== WALLET TRANSACTIONS TABLE ==={$nl}";
    $stmt = $pdo->prepare("
        SELECT id, type, amount, balance_after, description, created_at
        FROM wallet_transactions
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$userId]);
    $transactions = $stmt->fetchAll();

    if (empty($transactions)) {
        echo "<span class='warn'>❌ NO WALLET TRANSACTIONS FOUND{$nl}{$nl}</span>";
    } else {
        if (!$isCLI) {
            echo "<table><tr><th>ID</th><th>Type</th><th>Amount</th><th>Balance After</th><th>Description</th><th>Created</th></tr>";
            foreach ($transactions as $t) {
                $typeClass = $t['type'] === 'credit' ? 'success' : 'warn';
                echo "<tr><td>{$t['id']}</td>";
                echo "<td class='{$typeClass}'>{$t['type']}</td>";
                echo "<td>Rp " . number_format($t['amount'], 0, ',', '.') . "</td>";
                echo "<td>Rp " . number_format($t['balance_after'], 0, ',', '.') . "</td>";
                echo "<td>{$t['description']}</td>";
                echo "<td>{$t['created_at']}</td></tr>";
            }
            echo "</table>";
        } else {
            foreach ($transactions as $t) {
                echo "Type: {$t['type']}, Amount: Rp " . number_format($t['amount'], 0, ',', '.') .
                     ", Description: {$t['description']}, Created: {$t['created_at']}{$nl}";
            }
        }

        $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM wallet_transactions WHERE user_id = ? AND type = 'credit'");
        $stmt->execute([$userId]);
        $creditTotal = floatval($stmt->fetchColumn());
        echo "{$nl}Total CREDIT Transactions: Rp " . number_format($creditTotal, 0, ',', '.') . "{$nl}{$nl}";
    }

    // Check orders
    echo "=== ORDERS (MIDTRANS) ==={$nl}";
    $stmt = $pdo->prepare("
        SELECT id, total_amount, payment_method, payment_status, created_at
        FROM orders
        WHERE user_id = ? AND payment_method = 'midtrans'
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll();

    if (empty($orders)) {
        echo "<span class='warn'>❌ NO ORDERS FOUND{$nl}{$nl}</span>";
    } else {
        if (!$isCLI) {
            echo "<table><tr><th>ID</th><th>Amount</th><th>Payment Status</th><th>Created</th></tr>";
            foreach ($orders as $o) {
                $statusClass = $o['payment_status'] === 'paid' ? 'success' : 'warn';
                echo "<tr><td>{$o['id']}</td>";
                echo "<td>Rp " . number_format($o['total_amount'], 0, ',', '.') . "</td>";
                echo "<td class='{$statusClass}'>{$o['payment_status']}</td>";
                echo "<td>{$o['created_at']}</td></tr>";
            }
            echo "</table>";
        } else {
            foreach ($orders as $o) {
                echo "ID: {$o['id']}, Amount: Rp " . number_format($o['total_amount'], 0, ',', '.') .
                     ", Status: {$o['payment_status']}, Created: {$o['created_at']}{$nl}";
            }
        }

        $stmt = $pdo->prepare("SELECT SUM(total_amount) as total FROM orders WHERE user_id = ? AND payment_method = 'midtrans' AND payment_status = 'paid'");
        $stmt->execute([$userId]);
        $orderTotal = floatval($stmt->fetchColumn());
        echo "{$nl}Total PAID Orders: Rp " . number_format($orderTotal, 0, ',', '.') . "{$nl}{$nl}";
    }

    echo "=== DIAGNOSIS ==={$nl}";
    if ($user['wallet_balance'] > 0 && empty($topups) && empty($transactions)) {
        echo "<span class='error'>⚠️ CRITICAL: Wallet has balance but NO transaction records!{$nl}";
        echo "This indicates manual database manipulation.{$nl}{$nl}</span>";

        echo "<span class='warn'>RECOMMENDED ACTION:{$nl}";
        echo "Create a manual topup record or adjust total_topup to match wallet_balance.{$nl}</span>";
    }

} catch (Exception $e) {
    echo "<span class='error'>ERROR: " . $e->getMessage() . "{$nl}</span>";
}

if (!$isCLI) {
    echo "</body></html>";
}
