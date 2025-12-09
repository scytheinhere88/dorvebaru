<?php
/**
 * Fix Addresses: Add Postal Code & GPS Coordinates
 * Ini akan help user yang punya alamat tapi ga lengkap
 */
require_once __DIR__ . '/config.php';

echo "=== FIX ADDRESSES - ADD POSTAL CODE & GPS ===\n\n";

// Get addresses without GPS or postal
$stmt = $pdo->query("
    SELECT id, user_id, recipient_name, address, postal_code, latitude, longitude
    FROM user_addresses
    WHERE (postal_code IS NULL OR postal_code = ''
           OR latitude IS NULL OR latitude = 0
           OR longitude IS NULL OR longitude = 0)
    LIMIT 50
");

$addresses = $stmt->fetchAll();

if (empty($addresses)) {
    echo "✅ All addresses are complete!\n";
    exit;
}

echo "Found " . count($addresses) . " addresses that need fixing:\n\n";

foreach ($addresses as $addr) {
    echo "ID: {$addr['id']} - User: {$addr['user_id']}\n";
    echo "Name: {$addr['recipient_name']}\n";
    echo "Address: " . substr($addr['address'], 0, 80) . "...\n";
    echo "Current Postal: " . ($addr['postal_code'] ?: '❌ MISSING') . "\n";
    echo "Current GPS: " . ($addr['latitude'] && $addr['longitude'] ? "✅ {$addr['latitude']}, {$addr['longitude']}" : '❌ MISSING') . "\n";

    // Try to extract postal code from address
    $postal = null;
    if (preg_match('/\b(\d{5})\b/', $addr['address'], $matches)) {
        $postal = $matches[1];
        echo "📍 Found postal in address: $postal\n";
    }

    // Detect city from address for default postal codes
    $addressLower = strtolower($addr['address']);
    $defaultPostal = null;
    $defaultLat = null;
    $defaultLng = null;

    // Common Indonesian cities with postal codes
    $cityPostals = [
        'jakarta' => ['10110', -6.2088, 106.8456],
        'bandung' => ['40111', -6.9175, 107.6191],
        'surabaya' => ['60119', -7.2575, 112.7521],
        'medan' => ['20111', 3.5952, 98.6722],
        'bekasi' => ['17111', -6.2383, 106.9756],
        'tangerang' => ['15111', -6.1783, 106.6319],
        'depok' => ['16411', -6.4025, 106.7942],
        'semarang' => ['50131', -6.9667, 110.4167],
        'palembang' => ['30111', -2.9761, 104.7754],
        'makassar' => ['90111', -5.1477, 119.4327],
        'yogyakarta' => ['55111', -7.7956, 110.3695],
        'malang' => ['65111', -7.9666, 112.6326],
        'bogor' => ['16111', -6.5950, 106.7967],
        'binjai' => ['20719', 3.6001, 98.4854],
    ];

    foreach ($cityPostals as $city => $data) {
        if (strpos($addressLower, $city) !== false) {
            $defaultPostal = $data[0];
            $defaultLat = $data[1];
            $defaultLng = $data[2];
            echo "🏙️ Detected city: " . ucfirst($city) . "\n";
            break;
        }
    }

    // Update if we found postal or can set defaults
    $updates = [];
    $params = [];

    if (!$addr['postal_code'] && ($postal || $defaultPostal)) {
        $updates[] = "postal_code = ?";
        $params[] = $postal ?: $defaultPostal;
        echo "✅ Will set postal: " . ($postal ?: $defaultPostal) . "\n";
    }

    if ((!$addr['latitude'] || $addr['latitude'] == 0) && $defaultLat) {
        $updates[] = "latitude = ?";
        $params[] = $defaultLat;
        echo "✅ Will set latitude: $defaultLat\n";
    }

    if ((!$addr['longitude'] || $addr['longitude'] == 0) && $defaultLng) {
        $updates[] = "longitude = ?";
        $params[] = $defaultLng;
        echo "✅ Will set longitude: $defaultLng\n";
    }

    if (!empty($updates)) {
        $sql = "UPDATE user_addresses SET " . implode(', ', $updates) . " WHERE id = ?";
        $params[] = $addr['id'];

        $updateStmt = $pdo->prepare($sql);
        $updateStmt->execute($params);

        echo "✅ UPDATED!\n";
    } else {
        echo "⚠️ Cannot auto-fix - needs manual update\n";
    }

    echo "------------------------\n\n";
}

echo "\n=== SUMMARY ===\n";
echo "Check /member/address-book.php to verify updates\n";
echo "Users can also manually add GPS coordinates there\n";
?>
