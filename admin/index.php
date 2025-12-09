<?php
require_once __DIR__ . '/../config.php';

// Debug: Log admin check
error_log("Admin index.php accessed - isLoggedIn: " . (isLoggedIn() ? 'true' : 'false') . ", isAdmin: " . (isAdmin() ? 'true' : 'false'));
error_log("Session role: " . ($_SESSION['role'] ?? 'not set') . ", is_admin: " . ($_SESSION['is_admin'] ?? 'not set'));

if (!isAdmin()) {
    error_log("Admin check failed - redirecting to login");
    redirect('/admin/login.php');
}

// Get statistics
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

// Get pending deposits from wallet_transactions
try {
    $pending_deposits = $pdo->query("SELECT COUNT(*) FROM wallet_transactions WHERE type IN ('topup', 'deposit') AND status = 'pending'")->fetchColumn();
} catch (Exception $e) {
    $pending_deposits = 0;
}

$recent_orders = $pdo->query("SELECT o.*, u.name as customer_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5")->fetchAll();

$page_title = 'Dashboard - Admin';
include __DIR__ . '/includes/admin-header.php';
?>

<div class="header">
    <h1>Dashboard</h1>
    <p style="color: #6B7280; margin-top: 8px;">Selamat datang di Admin Panel Dorve.id</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value"><?php echo $total_users; ?></div>
        <div class="stat-label">Total Users</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $total_products; ?></div>
        <div class="stat-label">Total Products</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $total_orders; ?></div>
        <div class="stat-label">Total Orders</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $pending_deposits; ?></div>
        <div class="stat-label">Pending Deposits</div>
    </div>
</div>

<!-- Quick Actions -->
<style>
@media (max-width: 768px) {
    .quick-action-grid { grid-template-columns: 1fr !important; }
    .quick-action-card { padding: 16px !important; gap: 12px !important; }
    .quick-action-icon { width: 48px !important; height: 48px !important; font-size: 28px !important; }
}
@media (max-width: 480px) {
    .quick-action-icon { width: 44px !important; height: 44px !important; font-size: 24px !important; }
}
</style>
<div style="margin-top: 32px; margin-bottom: 32px;">
    <h2 style="font-size: 20px; font-weight: 600; margin-bottom: 20px; color: #1F2937;">⚡ Quick Actions</h2>

    <div class="quick-action-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
        <a href="/admin/orders/index.php?status=pending" class="quick-action-card" style="background: white; border: 2px solid #E5E7EB; border-radius: 12px; padding: 20px; display: flex; align-items: center; gap: 16px; text-decoration: none; transition: all 0.3s; cursor: pointer;">
            <div class="quick-action-icon" style="font-size: 32px; width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #F3F4F6 0%, #E5E7EB 100%); border-radius: 12px; flex-shrink: 0;">📦</div>
            <div style="flex: 1;">
                <div style="font-size: 16px; font-weight: 700; color: #1F2937; margin-bottom: 4px;">Pesanan Baru</div>
                <div style="font-size: 13px; color: #6B7280;">Kelola pesanan pending</div>
            </div>
        </a>

        <a href="/admin/products/add.php" class="quick-action-card" style="background: white; border: 2px solid #E5E7EB; border-radius: 12px; padding: 20px; display: flex; align-items: center; gap: 16px; text-decoration: none; transition: all 0.3s; cursor: pointer;">
            <div class="quick-action-icon" style="font-size: 32px; width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #F3F4F6 0%, #E5E7EB 100%); border-radius: 12px; flex-shrink: 0;">➕</div>
            <div style="flex: 1;">
                <div style="font-size: 16px; font-weight: 700; color: #1F2937; margin-bottom: 4px;">Tambah Produk</div>
                <div style="font-size: 13px; color: #6B7280;">Produk baru ke katalog</div>
            </div>
        </a>

        <a href="/admin/settings/index.php" class="quick-action-card" style="background: white; border: 2px solid #E5E7EB; border-radius: 12px; padding: 20px; display: flex; align-items: center; gap: 16px; text-decoration: none; transition: all 0.3s; cursor: pointer;">
            <div class="quick-action-icon" style="font-size: 32px; width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #F3F4F6 0%, #E5E7EB 100%); border-radius: 12px; flex-shrink: 0;">⚙️</div>
            <div style="flex: 1;">
                <div style="font-size: 16px; font-weight: 700; color: #1F2937; margin-bottom: 4px;">Pengaturan</div>
                <div style="font-size: 13px; color: #6B7280;">Konfigurasi toko</div>
            </div>
        </a>

        <a href="/admin/integration/error-logs.php" class="quick-action-card" style="background: white; border: 2px solid #E5E7EB; border-radius: 12px; padding: 20px; display: flex; align-items: center; gap: 16px; text-decoration: none; transition: all 0.3s; cursor: pointer;">
            <div class="quick-action-icon" style="font-size: 32px; width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #F3F4F6 0%, #E5E7EB 100%); border-radius: 12px; flex-shrink: 0;">📊</div>
            <div style="flex: 1;">
                <div style="font-size: 16px; font-weight: 700; color: #1F2937; margin-bottom: 4px;">Error Logs</div>
                <div style="font-size: 13px; color: #6B7280;">Monitor webhook & errors</div>
            </div>
        </a>

        <a href="/admin/settings/api-settings.php" class="quick-action-card" style="background: white; border: 2px solid #E5E7EB; border-radius: 12px; padding: 20px; display: flex; align-items: center; gap: 16px; text-decoration: none; transition: all 0.3s; cursor: pointer;">
            <div class="quick-action-icon" style="font-size: 32px; width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #F3F4F6 0%, #E5E7EB 100%); border-radius: 12px; flex-shrink: 0;">🔌</div>
            <div style="flex: 1;">
                <div style="font-size: 16px; font-weight: 700; color: #1F2937; margin-bottom: 4px;">API Settings</div>
                <div style="font-size: 13px; color: #6B7280;">Biteship & Midtrans</div>
            </div>
        </a>

        <a href="/admin/vouchers/add.php" class="quick-action-card" style="background: white; border: 2px solid #E5E7EB; border-radius: 12px; padding: 20px; display: flex; align-items: center; gap: 16px; text-decoration: none; transition: all 0.3s; cursor: pointer;">
            <div class="quick-action-icon" style="font-size: 32px; width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #F3F4F6 0%, #E5E7EB 100%); border-radius: 12px; flex-shrink: 0;">🎫</div>
            <div style="flex: 1;">
                <div style="font-size: 16px; font-weight: 700; color: #1F2937; margin-bottom: 4px;">Buat Voucher</div>
                <div style="font-size: 13px; color: #6B7280;">Voucher & promo baru</div>
            </div>
        </a>
    </div>
</div>

<script>
// Add hover effects to action cards
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.quick-action-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.borderColor = '#3B82F6';
            this.style.transform = 'translateY(-4px)';
            this.style.boxShadow = '0 8px 24px rgba(59, 130, 246, 0.2)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.borderColor = '#E5E7EB';
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = 'none';
        });
    });
});
</script>

<div class="content-container">
    <h2 style="margin-bottom: 20px; font-size: 20px; font-weight: 600;">Recent Orders</h2>
    <table>
        <thead>
            <tr>
                <th>Order Number</th>
                <th>Customer</th>
                <th>Total</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($recent_orders)): ?>
                <tr><td colspan="5" style="text-align: center; padding: 40px; color: #6B7280;">No orders yet</td></tr>
            <?php else: ?>
                <?php foreach ($recent_orders as $order): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                        <td><strong>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></strong></td>
                        <td><span style="padding: 6px 12px; background: #FEF3C7; color: #92400E; border-radius: 6px; font-size: 12px; font-weight: 600;"><?php echo ucfirst($order['payment_status']); ?></span></td>
                        <td><?php echo date('d M Y', strtotime($order['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
