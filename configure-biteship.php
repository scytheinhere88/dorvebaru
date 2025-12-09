<?php
/**
 * Configure Biteship Integration
 * Updates API key and store address
 */

require_once __DIR__ . '/config.php';

echo "<h1>🚀 Biteship Configuration</h1>";
echo "<style>body{font-family:monospace;padding:20px;background:#f9f9f9;} .success{color:green;font-weight:bold;} .error{color:red;font-weight:bold;} .info{color:blue;} pre{background:#fff;padding:15px;border:1px solid #ddd;border-radius:5px;} .box{background:#fff;padding:20px;margin:20px 0;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);}</style>";

// Configuration
$BITESHIP_API_KEY = 'biteship_live.eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJuYW1lIjoiRG9ydmUuaWQiLCJ1c2VySWQiOiI2OTI4NDVhNDM4MzQ5ZjAyZjdhM2VhNDgiLCJpYXQiOjE3NjQ2NTYwMjV9.xmkeeT2ghfHPe7PItX5HJ0KptlC5xbIhL1TlHWn6S1U';
$STORE_ADDRESS = 'Gang Hello Ponsel, Jl. Anggur Lk. VII No.43C, Bandar Senembah, Kec. Binjai Bar., Kota Binjai, Sumatera Utara 20719';
$STORE_CITY = 'Binjai';
$STORE_PROVINCE = 'Sumatera Utara';
$STORE_POSTAL_CODE = '20719';

$errors = [];
$success = [];

try {
    $pdo->beginTransaction();

    // 1. Setup Biteship in payment_gateway_settings
    echo "<div class='box'><h2>1️⃣ Configuring Biteship API Key...</h2>";
    try {
        // Check if table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'payment_gateway_settings'");
        if ($stmt->rowCount() == 0) {
            throw new Exception("Table payment_gateway_settings does not exist. Run setup-biteship-database.php first!");
        }

        // Check if biteship exists
        $stmt = $pdo->query("SELECT id FROM payment_gateway_settings WHERE gateway_name = 'biteship'");
        if ($stmt->rowCount() > 0) {
            // Update existing
            $stmt = $pdo->prepare("UPDATE payment_gateway_settings
                                  SET api_key = ?, is_production = 1, is_active = 1, updated_at = NOW()
                                  WHERE gateway_name = 'biteship'");
            $stmt->execute([$BITESHIP_API_KEY]);
            echo "<p class='success'>✓ Updated Biteship API key</p>";
        } else {
            // Insert new
            $stmt = $pdo->prepare("INSERT INTO payment_gateway_settings (gateway_name, api_key, is_production, is_active)
                                  VALUES ('biteship', ?, 1, 1)");
            $stmt->execute([$BITESHIP_API_KEY]);
            echo "<p class='success'>✓ Created Biteship configuration</p>";
        }
        $success[] = "Biteship API configured";
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
        $errors[] = "Biteship API: " . $e->getMessage();
    }
    echo "</div>";

    // 2. Setup system_settings table
    echo "<div class='box'><h2>2️⃣ Setting up system_settings...</h2>";
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

    // 3. Configure store address
    echo "<div class='box'><h2>3️⃣ Configuring Store Address...</h2>";
    try {
        $storeSettings = [
            ['store_name', 'Dorve.id Official Store'],
            ['store_phone', '+62-813-7737-8859'],
            ['store_address', $STORE_ADDRESS],
            ['store_city', $STORE_CITY],
            ['store_province', $STORE_PROVINCE],
            ['store_postal_code', $STORE_POSTAL_CODE],
            ['store_country', 'ID'],
            ['biteship_default_couriers', 'jne,jnt,sicepat,anteraja,idexpress,ninja']
        ];

        foreach ($storeSettings as $setting) {
            $stmt = $pdo->prepare("SELECT id FROM system_settings WHERE setting_key = ?");
            $stmt->execute([$setting[0]]);

            if ($stmt->rowCount() > 0) {
                // Update
                $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
                $stmt->execute([$setting[1], $setting[0]]);
                echo "<p class='success'>✓ Updated: {$setting[0]} = {$setting[1]}</p>";
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)");
                $stmt->execute($setting);
                echo "<p class='success'>✓ Created: {$setting[0]} = {$setting[1]}</p>";
            }
        }
        $success[] = "Store address configured";
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
        $errors[] = "Store settings: " . $e->getMessage();
    }
    echo "</div>";

    $pdo->commit();

    // Success Summary
    echo "<div class='box' style='background: linear-gradient(135deg, #10B981 0%, #059669 100%); color: white;'>";
    echo "<h2 style='color:white;'>✅ BITESHIP CONFIGURED SUCCESSFULLY!</h2>";
    echo "<p><strong>Configuration completed:</strong></p>";
    echo "<ul>";
    foreach ($success as $item) {
        echo "<li>✓ $item</li>";
    }
    echo "</ul>";
    echo "</div>";

    if (!empty($errors)) {
        echo "<div class='box' style='background: #FEE2E2;'>";
        echo "<h2 style='color: #DC2626;'>⚠️ Some Errors:</h2>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li style='color: #991B1B;'>✗ $error</li>";
        }
        echo "</ul>";
        echo "</div>";
    }

    // Verify Configuration
    echo "<div class='box'>";
    echo "<h2>🔍 Current Configuration</h2>";

    // Check Biteship settings
    $stmt = $pdo->query("SELECT * FROM payment_gateway_settings WHERE gateway_name = 'biteship'");
    $biteship = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "<h3>Biteship API:</h3>";
    echo "<table style='width:100%;border-collapse:collapse;'>";
    echo "<tr><td style='padding:8px;border:1px solid #ddd;'><strong>API Key</strong></td><td style='padding:8px;border:1px solid #ddd;'>" . substr($biteship['api_key'], 0, 30) . "...</td></tr>";
    echo "<tr><td style='padding:8px;border:1px solid #ddd;'><strong>Environment</strong></td><td style='padding:8px;border:1px solid #ddd;'>" . ($biteship['is_production'] ? 'PRODUCTION' : 'SANDBOX') . "</td></tr>";
    echo "<tr><td style='padding:8px;border:1px solid #ddd;'><strong>Status</strong></td><td style='padding:8px;border:1px solid #ddd;'>" . ($biteship['is_active'] ? '✅ ACTIVE' : '❌ INACTIVE') . "</td></tr>";
    echo "</table>";

    // Check store settings
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE 'store_%'");
    $storeSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    echo "<h3 style='margin-top:20px;'>Store Address:</h3>";
    echo "<table style='width:100%;border-collapse:collapse;'>";
    foreach ($storeSettings as $key => $value) {
        $displayKey = str_replace('store_', '', $key);
        echo "<tr><td style='padding:8px;border:1px solid #ddd;'><strong>" . ucwords(str_replace('_', ' ', $displayKey)) . "</strong></td><td style='padding:8px;border:1px solid #ddd;'>$value</td></tr>";
    }
    echo "</table>";
    echo "</div>";

    // Test API Connection
    echo "<div class='box'>";
    echo "<h2>🧪 Testing API Connection...</h2>";
    try {
        require_once __DIR__ . '/includes/BiteshipClient.php';
        $client = new BiteshipClient();

        // Test with a simple area search
        $testResult = $client->getAreas('Jakarta');

        if ($testResult['success']) {
            echo "<p class='success'>✓ API Connection SUCCESSFUL!</p>";
            echo "<p class='info'>Biteship API is working correctly</p>";
        } else {
            echo "<p class='error'>✗ API Connection FAILED</p>";
            echo "<p class='error'>Error: " . ($testResult['error'] ?? 'Unknown error') . "</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>✗ Could not test API: " . $e->getMessage() . "</p>";
    }
    echo "</div>";

    // Next Steps
    echo "<div class='box'>";
    echo "<h2>📋 NEXT STEPS:</h2>";
    echo "<ol style='line-height: 2;'>";
    echo "<li><strong>Test Checkout:</strong><br>Add products to cart and try checkout with shipping calculation</li>";
    echo "<li><strong>Configure Webhook (Optional):</strong><br>Add this URL to Biteship Dashboard:<br><code style='background:#f0f0f0;padding:4px 8px;border-radius:4px;'>https://dorve.id/api/biteship/webhook.php</code></li>";
    echo "<li><strong>Monitor Logs:</strong><br>Check error logs if shipping calculation doesn't work</li>";
    echo "</ol>";
    echo "</div>";

    echo "<div style='text-align:center;margin:40px 0;'>";
    echo "<a href='/pages/checkout.php' style='display:inline-block;padding:16px 32px;background:#10B981;color:white;text-decoration:none;border-radius:8px;font-weight:bold;font-size:16px;margin-right:12px;'>🛒 Test Checkout</a>";
    echo "<a href='/admin/' style='display:inline-block;padding:16px 32px;background:#3B82F6;color:white;text-decoration:none;border-radius:8px;font-weight:bold;font-size:16px;'>🏠 Go to Admin</a>";
    echo "</div>";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "<div class='box' style='background:#FEE2E2;color:#DC2626;'>";
    echo "<h2>❌ CONFIGURATION FAILED!</h2>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

echo "<hr style='margin:40px 0;'>";
echo "<p style='text-align:center;color:#666;font-size:13px;'>Configuration completed at: " . date('Y-m-d H:i:s') . "</p>";
echo "<p style='text-align:center;color:#666;font-size:12px;'>You can safely delete this file after configuration: configure-biteship.php</p>";
?>
