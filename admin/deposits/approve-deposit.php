<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/referral-helper.php';
require_once __DIR__ . '/../../includes/tier-helper.php';

if (!isAdmin()) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Invalid request method']));
}

$deposit_id = intval($_POST['deposit_id'] ?? 0);
$admin_notes = trim($_POST['admin_notes'] ?? '');

if ($deposit_id <= 0) {
    die(json_encode(['success' => false, 'message' => 'Invalid deposit ID']));
}

try {
    $pdo->beginTransaction();
    
    // Get deposit info
    $stmt = $pdo->prepare("SELECT * FROM topups WHERE id = ? AND status = 'pending'");
    $stmt->execute([$deposit_id]);
    $deposit = $stmt->fetch();
    
    if (!$deposit) {
        throw new Exception('Deposit not found or already processed');
    }
    
    // Update deposit status
    $stmt = $pdo->prepare("
        UPDATE topups 
        SET status = 'completed', 
            admin_notes = ?, 
            completed_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$admin_notes, $deposit_id]);
    
    // Add balance to user wallet and update total_topup
    $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ?, total_topup = total_topup + ? WHERE id = ?");
    $stmt->execute([$deposit['amount'], $deposit['amount'], $deposit['user_id']]);
    
    // Create wallet transaction record
    $stmt = $pdo->prepare("
        INSERT INTO wallet_transactions
        (user_id, type, amount, balance_before, balance_after, description, status, payment_status, reference_id, created_at)
        VALUES (?, 'topup', ?,
            (SELECT wallet_balance - ? FROM users WHERE id = ?),
            (SELECT wallet_balance FROM users WHERE id = ?),
            ?, 'approved', 'paid', ?, NOW())
    ");
    
    $description = 'Wallet topup approved by admin';
    if ($admin_notes) {
        $description .= ' - ' . $admin_notes;
    }
    
    $stmt->execute([
        $deposit['user_id'],
        $deposit['amount'],
        $deposit['amount'],
        $deposit['user_id'],
        $deposit['user_id'],
        $description,
        'TOP-' . $deposit_id
    ]);
    
    $pdo->commit();

    // Update user tier based on total_topup
    $tier_result = updateUserTier($pdo, $deposit['user_id']);

    // Check if this is first topup and process referral reward
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM topups 
        WHERE user_id = ? AND status = 'completed'
    ");
    $stmt->execute([$deposit['user_id']]);
    $topup_count = $stmt->fetchColumn();
    
    $success_message = 'Deposit approved successfully!';

    // Add tier upgrade message if applicable
    if (isset($tier_result['changed']) && $tier_result['changed']) {
        $tier_info = getTierInfo($tier_result['new_tier']);
        $success_message .= ' User upgraded to ' . $tier_info['name'] . ' tier!';
    }

    if ($topup_count == 1) {
        // This is first topup, process referral reward
        $reward_result = processReferralReward($deposit['user_id'], $deposit['amount']);

        if ($reward_result['success']) {
            $success_message .= ' Referral reward of Rp ' . number_format($reward_result['commission'], 0, ',', '.') . ' has been awarded.';
        }
    }

    $_SESSION['success'] = $success_message;
    
    redirect('/admin/deposits/index.php');
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Deposit approval error: " . $e->getMessage());
    $_SESSION['error'] = 'Failed to approve deposit: ' . $e->getMessage();
    redirect('/admin/deposits/index.php');
}
