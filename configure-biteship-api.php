<?php
require_once 'config.php';

echo "<h2>🚀 Configuring Biteship API...</h2>";
echo "<style>body{font-family:monospace;padding:20px;background:#f9f9f9;} pre{background:#fff;padding:15px;border:1px solid #ddd;border-radius:5px;} .success{color:green;font-weight:bold;} .error{color:red;font-weight:bold;}</style>";
echo "<pre>";

$apiKey = 'biteship_live.eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJuYW1lIjoiRG9ydmUuaWQiLCJ1c2VySWQiOiI2OTI4NDVhNDM4MzQ5ZjAyZjdhM2VhNDgiLCJpYXQiOjE3NjQ2NTYwMjV9.xmkeeT2ghfHPe7PItX5HJ0KptlC5xbIhL1TlHWn6S1U';

try {
    echo "=" . str_repeat("=", 70) . "\n";
    echo "   BITESHIP API CONFIGURATION\n";
    echo "=" . str_repeat("=", 70) . "\n\n";

    // 1. Configure Biteship API Key
    echo "1. Configuring Biteship API Key...\n";

    $stmt = $pdo->prepare("SELECT * FROM payment_gateway_settings WHERE gateway_name = 'biteship'");
    $stmt->execute();
    $existing = $stmt->fetch();

    if ($existing) {
        echo "   Updating existing Biteship config...\n";
        $stmt = $pdo->prepare("UPDATE payment_gateway_settings
                               SET api_key = ?,
                                   is_production = 1,
                                   is_active = 1
                               WHERE gateway_name = 'biteship'");
        $stmt->execute([$apiKey]);
        echo "   <span class='success'>✅ Updated!</span>\n";
    } else {
        echo "   Creating new Biteship config...\n";
        $stmt = $pdo->prepare("INSERT INTO payment_gateway_settings
                               (gateway_name, api_key, is_production, is_active)
                               VALUES ('biteship', ?, 1, 1)");
        $stmt->execute([$apiKey]);
        echo "   <span class='success'>✅ Created!</span>\n";
    }

    // 2. Configure Store Address (Origin)
    echo "\n2. Configuring Store Address (Shipping Origin)...\n";

    $storeSettings = [
        'store_name' => 'Dorve.id Official Store',
        'store_phone' => '+6281377378859',
        'store_address' => 'Jl. Anggur No. 43C (Gang Hello Ponsel) Lk. VII, Kelurahan Bandar Senembah, Kecamatan Binjai Barat',
        'store_city' => 'Binjai',
        'store_province' => 'Sumatera Utara',
        'store_postal_code' => '20719',
        'store_country' => 'ID',
        'biteship_default_couriers' => 'jne,jnt,sicepat,anteraja,idexpress,ninja,pos'
    ];

    foreach ($storeSettings as $key => $value) {
        $stmt = $pdo->prepare("SELECT * FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $existing = $stmt->fetch();

        if ($existing) {
            $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
            echo "   ✅ Updated: $key\n";
        } else {
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->execute([$key, $value]);
            echo "   ✅ Created: $key\n";
        }
    }

    // 3. Verify Configuration
    echo "\n3. Verifying Configuration...\n";

    $stmt = $pdo->query("SELECT api_key, is_production, is_active FROM payment_gateway_settings WHERE gateway_name = 'biteship'");
    $config = $stmt->fetch();

    echo "   API Key: " . substr($config['api_key'], 0, 30) . "... ✅\n";
    echo "   Environment: " . ($config['is_production'] ? 'PRODUCTION 🔴' : 'SANDBOX 🟡') . "\n";
    echo "   Status: " . ($config['is_active'] ? 'ACTIVE ✅' : 'INACTIVE ❌') . "\n";

    echo "\n4. Store Address Configuration:\n";
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE 'store_%'");
    while ($row = $stmt->fetch()) {
        $key = str_replace('store_', '', $row['setting_key']);
        echo "   - " . ucwords(str_replace('_', ' ', $key)) . ": " . $row['setting_value'] . "\n";
    }

    echo "\n";
    echo "=" . str_repeat("=", 70) . "\n";
    echo "<span class='success'>✅ CONFIGURATION COMPLETED SUCCESSFULLY!</span>\n";
    echo "=" . str_repeat("=", 70) . "\n";
    echo "\n";
    echo "📋 Summary:\n";
    echo "   - API Key: Configured with LIVE key\n";
    echo "   - Environment: PRODUCTION mode\n";
    echo "   - Origin: Binjai, Sumatera Utara (20719)\n";
    echo "   - Couriers: JNE, JNT, SiCepat, AnterAja, ID Express, Ninja, POS\n";
    echo "\n";
    echo "🚀 Next Steps:\n";
    echo "   1. Go to checkout page and test shipping calculation\n";
    echo "   2. Add address and it should show real shipping rates\n";
    echo "   3. Delete this file after testing: configure-biteship-api.php\n";
    echo "\n";
    echo "🌐 Test URL: https://dorve.id/pages/checkout.php\n";
    echo "\n";

} catch (Exception $e) {
    echo "\n<span class='error'>❌ ERROR: " . $e->getMessage() . "</span>\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString();
}

echo "</pre>";
?>
