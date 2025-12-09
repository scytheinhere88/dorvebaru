<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Address Checker</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            margin-bottom: 32px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }
        .header h1 {
            font-size: 42px;
            color: #1F2937;
            margin-bottom: 12px;
        }
        .header p {
            font-size: 18px;
            color: #6B7280;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }
        .stat-number {
            font-size: 48px;
            font-weight: 900;
            margin-bottom: 8px;
        }
        .stat-label {
            font-size: 14px;
            color: #6B7280;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
        .stat-card.good { border-left: 6px solid #10B981; }
        .stat-card.good .stat-number { color: #10B981; }
        .stat-card.warning { border-left: 6px solid #F59E0B; }
        .stat-card.warning .stat-number { color: #F59E0B; }
        .stat-card.bad { border-left: 6px solid #EF4444; }
        .stat-card.bad .stat-number { color: #EF4444; }
        .address-grid {
            display: grid;
            gap: 24px;
        }
        .address-card {
            background: white;
            padding: 32px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }
        .address-card.valid {
            border-left: 6px solid #10B981;
        }
        .address-card.invalid {
            border-left: 6px solid #EF4444;
        }
        .address-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid #F3F4F6;
        }
        .address-title {
            font-size: 24px;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 4px;
        }
        .address-subtitle {
            font-size: 14px;
            color: #6B7280;
        }
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .status-badge.valid {
            background: #D1FAE5;
            color: #065F46;
        }
        .status-badge.invalid {
            background: #FEE2E2;
            color: #991B1B;
        }
        .address-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }
        .detail-item {
            background: #F9FAFB;
            padding: 16px;
            border-radius: 12px;
        }
        .detail-label {
            font-size: 11px;
            color: #6B7280;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .detail-value {
            font-size: 16px;
            color: #1F2937;
            font-weight: 600;
            word-break: break-word;
        }
        .detail-value.missing {
            color: #EF4444;
            font-style: italic;
        }
        .detail-value.present {
            color: #10B981;
        }
        .address-text {
            background: #F3F4F6;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            color: #374151;
            line-height: 1.6;
        }
        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 700;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            display: inline-block;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
        }
        .btn-danger {
            background: #EF4444;
            color: white;
        }
        .btn-danger:hover {
            background: #DC2626;
        }
        .btn-success {
            background: #10B981;
            color: white;
        }
        .btn-success:hover {
            background: #059669;
        }
        .fix-all-container {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            margin-top: 32px;
            color: white;
            box-shadow: 0 10px 40px rgba(16, 185, 129, 0.3);
        }
        .fix-all-container h2 {
            font-size: 32px;
            margin-bottom: 12px;
        }
        .fix-all-container p {
            font-size: 16px;
            margin-bottom: 24px;
            opacity: 0.95;
        }
        .empty-state {
            background: white;
            padding: 80px 40px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }
        .empty-state-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        .empty-state h2 {
            font-size: 32px;
            color: #1F2937;
            margin-bottom: 12px;
        }
        .empty-state p {
            font-size: 18px;
            color: #6B7280;
            margin-bottom: 32px;
        }
    </style>
</head>
<body>

<?php
require_once __DIR__ . '/config.php';

// Get all addresses
$stmt = $pdo->query("
    SELECT ua.*, u.name as user_name, u.email
    FROM user_addresses ua
    LEFT JOIN users u ON ua.user_id = u.id
    ORDER BY ua.user_id, ua.is_default DESC
");
$addresses = $stmt->fetchAll();

// Calculate stats
$total = count($addresses);
$valid = 0;
$invalid = 0;
$needsPostal = 0;
$needsGPS = 0;

foreach ($addresses as $addr) {
    $hasPostal = !empty($addr['postal_code']);
    $hasGPS = !empty($addr['latitude']) && !empty($addr['longitude']);

    if ($hasPostal || $hasGPS) {
        $valid++;
    } else {
        $invalid++;
    }

    if (!$hasPostal) $needsPostal++;
    if (!$hasGPS) $needsGPS++;
}
?>

<div class="container">
    <!-- Header -->
    <div class="header">
        <h1>🚚 Shipping Address Checker</h1>
        <p>Check which addresses are ready for shipping calculation</p>
    </div>

    <!-- Stats -->
    <div class="stats">
        <div class="stat-card">
            <div class="stat-number"><?= $total ?></div>
            <div class="stat-label">Total Addresses</div>
        </div>
        <div class="stat-card good">
            <div class="stat-number"><?= $valid ?></div>
            <div class="stat-label">✅ Valid</div>
        </div>
        <div class="stat-card bad">
            <div class="stat-number"><?= $invalid ?></div>
            <div class="stat-label">❌ Invalid</div>
        </div>
        <div class="stat-card warning">
            <div class="stat-number"><?= $needsPostal ?></div>
            <div class="stat-label">Need Postal</div>
        </div>
        <div class="stat-card warning">
            <div class="stat-number"><?= $needsGPS ?></div>
            <div class="stat-label">Need GPS</div>
        </div>
    </div>

    <?php if (empty($addresses)): ?>
        <!-- Empty State -->
        <div class="empty-state">
            <div class="empty-state-icon">📦</div>
            <h2>No Addresses Found</h2>
            <p>Users haven't added any shipping addresses yet</p>
            <a href="/member/address-book.php" class="btn btn-primary">Add First Address</a>
        </div>
    <?php else: ?>
        <!-- Address Grid -->
        <div class="address-grid">
            <?php foreach ($addresses as $addr):
                $hasPostal = !empty($addr['postal_code']);
                $hasGPS = !empty($addr['latitude']) && !empty($addr['longitude']);
                $isValid = $hasPostal || $hasGPS;
            ?>
                <div class="address-card <?= $isValid ? 'valid' : 'invalid' ?>">
                    <!-- Header -->
                    <div class="address-header">
                        <div>
                            <div class="address-title"><?= htmlspecialchars($addr['label']) ?></div>
                            <div class="address-subtitle">
                                <?= htmlspecialchars($addr['recipient_name']) ?> •
                                User: <?= htmlspecialchars($addr['user_name'] ?? $addr['user_id']) ?>
                                <?= $addr['is_default'] ? ' • <strong>DEFAULT</strong>' : '' ?>
                            </div>
                        </div>
                        <span class="status-badge <?= $isValid ? 'valid' : 'invalid' ?>">
                            <?= $isValid ? '✅ Valid' : '❌ Invalid' ?>
                        </span>
                    </div>

                    <!-- Address Text -->
                    <div class="address-text">
                        📍 <?= htmlspecialchars($addr['address']) ?>
                    </div>

                    <!-- Details -->
                    <div class="address-details">
                        <div class="detail-item">
                            <div class="detail-label">Phone</div>
                            <div class="detail-value"><?= htmlspecialchars($addr['phone']) ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Postal Code</div>
                            <div class="detail-value <?= $hasPostal ? 'present' : 'missing' ?>">
                                <?= $hasPostal ? htmlspecialchars($addr['postal_code']) : '❌ Missing' ?>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">GPS Coordinates</div>
                            <div class="detail-value <?= $hasGPS ? 'present' : 'missing' ?>">
                                <?php if ($hasGPS): ?>
                                    <?= number_format($addr['latitude'], 6) ?>, <?= number_format($addr['longitude'], 6) ?>
                                <?php else: ?>
                                    ❌ Missing
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="actions">
                        <?php if ($isValid): ?>
                            <span class="btn btn-success">✅ Ready for Shipping</span>
                        <?php else: ?>
                            <a href="/member/address-book.php" class="btn btn-danger">
                                ⚠️ Needs Update
                            </a>
                        <?php endif; ?>
                        <a href="/member/address-book.php" class="btn btn-primary">
                            ✏️ Edit Address
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($invalid > 0): ?>
            <!-- Fix All Button -->
            <div class="fix-all-container">
                <h2>🔧 Auto-Fix Available!</h2>
                <p><?= $invalid ?> address<?= $invalid > 1 ? 'es' : '' ?> need<?= $invalid > 1 ? '' : 's' ?> fixing. Run auto-fix to add postal codes and GPS coordinates.</p>
                <a href="/fix-addresses-geocode.php" class="btn btn-primary" style="background: white; color: #10B981; font-size: 18px; padding: 16px 32px;">
                    🚀 Run Auto-Fix Now
                </a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

</body>
</html>
