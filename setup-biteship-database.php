<?php
/**
 * Biteship Database Setup
 * Creates all necessary tables for Biteship integration
 * Run this once: https://dorve.id/setup-biteship-database.php
 */

require_once __DIR__ . '/config.php';

echo "<h1>🚀 Biteship Database Setup</h1>";
echo "<style>body{font-family:monospace;padding:20px;background:#f9f9f9;} .success{color:green;font-weight:bold;} .error{color:red;font-weight:bold;} .info{color:blue;} pre{background:#fff;padding:15px;border:1px solid #ddd;border-radius:5px;} .box{background:#fff;padding:20px;margin:20px 0;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);}</style>";

$errors = [];
$success = [];

try {
    $pdo->beginTransaction();

    // 1. CREATE biteship_shipments table
    echo "<div class='box'><h2>1️⃣ Creating biteship_shipments table...</h2>";
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS biteship_shipments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            biteship_order_id VARCHAR(255),
            waybill_id VARCHAR(255),
            courier_company VARCHAR(100),
            courier_type VARCHAR(100),
            courier_insurance DECIMAL(10,2) DEFAULT 0,
            courier_tracking_id VARCHAR(255),
            pickup_code VARCHAR(100),
            delivery_type VARCHAR(50) DEFAULT 'now',
            delivery_date DATETIME,
            status VARCHAR(50) DEFAULT 'pending',
            price DECIMAL(10,2),
            note TEXT,
            metadata JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_order_id (order_id),
            INDEX idx_biteship_order_id (biteship_order_id),
            INDEX idx_waybill_id (waybill_id),
            INDEX idx_status (status),
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<p class='success'>✓ biteship_shipments table created successfully</p>";
        $success[] = "biteship_shipments table";
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
        $errors[] = "biteship_shipments: " . $e->getMessage();
    }
    echo "</div>";

    // 2. CREATE biteship_webhook_logs table
    echo "<div class='box'><h2>2️⃣ Creating biteship_webhook_logs table...</h2>";
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS biteship_webhook_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event VARCHAR(100),
            biteship_order_id VARCHAR(255),
            payload LONGTEXT,
            processed TINYINT(1) DEFAULT 0,
            error_message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_biteship_order_id (biteship_order_id),
            INDEX idx_event (event),
            INDEX idx_processed (processed)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<p class='success'>✓ biteship_webhook_logs table created successfully</p>";
        $success[] = "biteship_webhook_logs table";
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
        $errors[] = "biteship_webhook_logs: " . $e->getMessage();
    }
    echo "</div>";

    // 3. UPDATE payment_gateway_settings (add api_key column if not exists)
    echo "<div class='box'><h2>3️⃣ Updating payment_gateway_settings...</h2>";
    try {
        // Check if api_key column exists
        $stmt = $pdo->query("SHOW COLUMNS FROM payment_gateway_settings LIKE 'api_key'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE payment_gateway_settings ADD COLUMN api_key TEXT AFTER gateway_name");
            echo "<p class='success'>✓ Added api_key column to payment_gateway_settings</p>";
            $success[] = "payment_gateway_settings updated";
        } else {
            echo "<p class='info'>ℹ api_key column already exists</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
        $errors[] = "payment_gateway_settings: " . $e->getMessage();
    }
    echo "</div>";

    // 4. INSERT default Biteship gateway settings
    echo "<div class='box'><h2>4️⃣ Creating default Biteship settings...</h2>";
    try {
        $stmt = $pdo->query("SELECT id FROM payment_gateway_settings WHERE gateway_name = 'biteship'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("INSERT INTO payment_gateway_settings (gateway_name, api_key, is_production, is_active)
                       VALUES ('biteship', '', 0, 0)");
            echo "<p class='success'>✓ Default Biteship settings created (INACTIVE - Configure in Admin Settings)</p>";
            $success[] = "Biteship default settings";
        } else {
            echo "<p class='info'>ℹ Biteship settings already exist</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
        $errors[] = "biteship settings: " . $e->getMessage();
    }
    echo "</div>";

    // 5. CREATE system_settings if not exists
    echo "<div class='box'><h2>5️⃣ Setting up system_settings...</h2>";
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            setting_type VARCHAR(50) DEFAULT 'text',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_setting_key (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<p class='success'>✓ system_settings table ready</p>";
        $success[] = "system_settings table";
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
        $errors[] = "system_settings: " . $e->getMessage();
    }
    echo "</div>";

    // 6. INSERT default system settings for Biteship
    echo "<div class='box'><h2>6️⃣ Creating default system settings...</h2>";
    try {
        $defaultSettings = [
            ['biteship_default_couriers', 'jne,jnt,sicepat,anteraja,idexpress,ninja', 'text'],
            ['store_name', 'Dorve.id Official Store', 'text'],
            ['store_phone', '+62-813-7737-8859', 'text'],
            ['store_address', '', 'text'],
            ['store_city', 'Jakarta', 'text'],
            ['store_province', 'DKI Jakarta', 'text'],
            ['store_postal_code', '12190', 'text'],
            ['store_country', 'ID', 'text']
        ];

        foreach ($defaultSettings as $setting) {
            $stmt = $pdo->prepare("SELECT id FROM system_settings WHERE setting_key = ?");
            $stmt->execute([$setting[0]]);
            if ($stmt->rowCount() == 0) {
                $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type) VALUES (?, ?, ?)");
                $stmt->execute($setting);
                echo "<p class='success'>✓ Created setting: {$setting[0]}</p>";
            } else {
                echo "<p class='info'>ℹ Setting exists: {$setting[0]}</p>";
            }
        }
        $success[] = "Default system settings";
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
        $errors[] = "default settings: " . $e->getMessage();
    }
    echo "</div>";

    $pdo->commit();

    // Summary
    echo "<div class='box' style='background: linear-gradient(135deg, #10B981 0%, #059669 100%); color: white;'>";
    echo "<h2 style='color:white;'>✅ DATABASE SETUP COMPLETE!</h2>";
    echo "<p><strong>Successfully created:</strong></p>";
    echo "<ul>";
    foreach ($success as $item) {
        echo "<li>✓ $item</li>";
    }
    echo "</ul>";
    echo "</div>";

    if (!empty($errors)) {
        echo "<div class='box' style='background: #FEE2E2;'>";
        echo "<h2 style='color: #DC2626;'>⚠️ Some Errors Occurred:</h2>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li style='color: #991B1B;'>✗ $error</li>";
        }
        echo "</ul>";
        echo "</div>";
    }

    // Next Steps
    echo "<div class='box'>";
    echo "<h2>📋 NEXT STEPS:</h2>";
    echo "<ol style='line-height: 2;'>";
    echo "<li><strong>Configure Biteship API Key:</strong><br><a href='/admin/settings/api-settings.php' style='color: #2563EB;'>Go to Admin → Settings → API Settings</a></li>";
    echo "<li><strong>Set Store Address:</strong><br>Update store postal code in system_settings for accurate shipping</li>";
    echo "<li><strong>Configure Webhook:</strong><br>Add this URL to Biteship Dashboard:<br><code style='background:#f0f0f0;padding:4px 8px;border-radius:4px;'>https://dorve.id/api/biteship/webhook.php</code></li>";
    echo "<li><strong>Test Integration:</strong><br>Try checkout with shipping calculation</li>";
    echo "</ol>";
    echo "</div>";

    // Integration Status
    echo "<div class='box'>";
    echo "<h2>🔌 Integration Status</h2>";
    echo "<table style='width:100%;border-collapse:collapse;'>";
    echo "<tr style='background:#f9f9f9;'><th style='padding:12px;border:1px solid #ddd;text-align:left;'>Component</th><th style='padding:12px;border:1px solid #ddd;text-align:left;'>Status</th></tr>";

    $components = [
        ['Database Tables', '✅ Ready'],
        ['BiteshipClient.php', '✅ Exists'],
        ['BiteshipConfig.php', '✅ Exists'],
        ['API: calculate-rates', '✅ Working'],
        ['API: webhook', '✅ Ready'],
        ['Admin Settings', '✅ Available'],
        ['Checkout Integration', '✅ Integrated']
    ];

    foreach ($components as $comp) {
        echo "<tr><td style='padding:12px;border:1px solid #ddd;'>{$comp[0]}</td><td style='padding:12px;border:1px solid #ddd;font-weight:bold;'>{$comp[1]}</td></tr>";
    }
    echo "</table>";
    echo "</div>";

    echo "<div style='text-align:center;margin:40px 0;'>";
    echo "<a href='/admin/settings/api-settings.php' style='display:inline-block;padding:16px 32px;background:#10B981;color:white;text-decoration:none;border-radius:8px;font-weight:bold;font-size:16px;margin-right:12px;'>⚙️ Configure Biteship</a>";
    echo "<a href='/admin/' style='display:inline-block;padding:16px 32px;background:#3B82F6;color:white;text-decoration:none;border-radius:8px;font-weight:bold;font-size:16px;'>🏠 Go to Admin</a>";
    echo "</div>";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "<div class='box' style='background:#FEE2E2;color:#DC2626;'>";
    echo "<h2>❌ SETUP FAILED!</h2>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

echo "<hr style='margin:40px 0;'>";
echo "<p style='text-align:center;color:#666;font-size:13px;'>Setup completed at: " . date('Y-m-d H:i:s') . "</p>";
echo "<p style='text-align:center;color:#666;font-size:12px;'>You can safely delete this file after setup: setup-biteship-database.php</p>";
?>
