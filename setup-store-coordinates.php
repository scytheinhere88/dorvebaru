<?php
/**
 * ADD STORE COORDINATES TO DATABASE
 * This enables distance-based shipping calculations
 */

require_once __DIR__ . '/config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Setup Store Coordinates</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin: 10px 0; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin: 10px 0; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 4px; margin: 10px 0; border: 1px solid #bee5eb; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin-top: 20px; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🗺️ Setup Store Coordinates</h1>";

try {
    // Check if coordinates already exist
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'store_latitude'");
    $stmt->execute();
    $existingLat = $stmt->fetchColumn();

    if ($existingLat) {
        echo "<div class='info'>✅ Coordinates already exist in database</div>";

        // Show existing values
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE 'store_%' AND setting_key IN ('store_latitude', 'store_longitude', 'store_postal_code', 'store_city')");
        $stmt->execute();
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        echo "<h3>Current Settings:</h3>";
        echo "<pre>";
        foreach ($settings as $key => $value) {
            echo htmlspecialchars($key) . ": " . htmlspecialchars($value) . "\n";
        }
        echo "</pre>";
    } else {
        echo "<div class='info'>📍 Adding store coordinates for Binjai...</div>";

        // Insert latitude
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, created_at) VALUES ('store_latitude', '-3.5952', NOW()) ON DUPLICATE KEY UPDATE setting_value = '-3.5952', updated_at = NOW()");
        $stmt->execute();

        // Insert longitude
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, created_at) VALUES ('store_longitude', '98.5006', NOW()) ON DUPLICATE KEY UPDATE setting_value = '98.5006', updated_at = NOW()");
        $stmt->execute();

        echo "<div class='success'>✅ Store coordinates added successfully!</div>";

        // Show what was added
        echo "<h3>Added Coordinates:</h3>";
        echo "<pre>";
        echo "store_latitude: -3.5952 (Binjai)\n";
        echo "store_longitude: 98.5006 (Binjai)\n";
        echo "</pre>";
    }

    // Show distance calculation examples
    echo "<h3>📏 Distance Examples:</h3>";
    echo "<div class='info'>";
    echo "<strong>From Binjai to:</strong><br>";
    echo "• Medan: ~20 km → 3 delivery options available<br>";
    echo "• Deli Serdang: ~15 km → 3 delivery options available<br>";
    echo "• Binjai (same city): ~5 km → 3 delivery options available<br>";
    echo "• Jakarta: ~1400 km → Uses Biteship couriers (JNE, J&T, etc)<br>";
    echo "</div>";

    // Show pricing structure
    echo "<h3>💰 Local Delivery Pricing:</h3>";
    echo "<pre>";
    echo "1. Kurir Instan (≤25km):\n";
    echo "   Base: Rp 15,000 + (distance × Rp 1,000/km)\n";
    echo "   Example: 20km = Rp 35,000\n";
    echo "   Delivery: Same day (3-6 jam)\n\n";

    echo "2. JNT Lokal (≤50km):\n";
    echo "   Base: Rp 10,000 + (distance × Rp 500/km)\n";
    echo "   Example: 20km = Rp 20,000\n";
    echo "   Delivery: 1 hari kerja\n\n";

    echo "3. Kurir Lokal Ekonomis (≤100km):\n";
    echo "   Base: Rp 8,000 + (distance × Rp 300/km)\n";
    echo "   Example: 20km = Rp 14,000\n";
    echo "   Delivery: 1-2 hari kerja\n";
    echo "</pre>";

    // Test links
    echo "<h3>🧪 Test Now:</h3>";
    echo "<a href='/debug-shipping.php?lat=-3.5952&lng=98.5006&postal=20239' class='btn'>Test Binjai → Binjai</a> ";
    echo "<a href='/debug-shipping.php?lat=-3.5952&lng=98.6722&postal=20111' class='btn'>Test Binjai → Medan</a> ";
    echo "<a href='/test-shipping-addresses.php' class='btn'>Test Tool</a>";

} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</div>
</body>
</html>";
?>
