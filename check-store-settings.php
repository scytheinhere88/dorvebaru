<?php
require_once __DIR__ . '/config.php';

echo "=== STORE SETTINGS CHECK ===\n\n";

// Check system_settings
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE 'store_%' OR setting_key LIKE 'biteship_%'");
    $settings = $stmt->fetchAll();

    echo "SYSTEM SETTINGS:\n";
    foreach ($settings as $s) {
        echo "  {$s['setting_key']}: {$s['setting_value']}\n";
    }
} catch (Exception $e) {
    echo "No system_settings table\n";
}

echo "\n";

// Check site_settings
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'store_%' OR setting_key LIKE 'biteship_%'");
    $settings = $stmt->fetchAll();

    echo "SITE SETTINGS:\n";
    foreach ($settings as $s) {
        echo "  {$s['setting_key']}: {$s['setting_value']}\n";
    }
} catch (Exception $e) {
    echo "No site_settings table\n";
}

echo "\n=== EXPECTED VALUES FOR BINJAI ===\n";
echo "store_latitude: 3.5952 (POSITIVE!)\n";
echo "store_longitude: 98.5006\n";
echo "store_postal_code: 20719\n";
?>
