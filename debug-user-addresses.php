<?php
require_once __DIR__ . '/config.php';

// Check user addresses
$stmt = $pdo->query("SELECT id, user_id, label, recipient_name, address, postal_code, latitude, longitude, is_default FROM user_addresses ORDER BY user_id, is_default DESC LIMIT 20");
$addresses = $stmt->fetchAll();

echo "=== USER ADDRESSES DEBUG ===\n\n";

if (empty($addresses)) {
    echo "❌ NO ADDRESSES FOUND!\n";
} else {
    foreach ($addresses as $addr) {
        echo "ID: {$addr['id']}\n";
        echo "User: {$addr['user_id']}\n";
        echo "Label: {$addr['label']}\n";
        echo "Name: {$addr['recipient_name']}\n";
        echo "Address: " . substr($addr['address'], 0, 50) . "...\n";
        echo "Postal: " . ($addr['postal_code'] ?: '❌ MISSING') . "\n";
        echo "Latitude: " . ($addr['latitude'] ?: '❌ MISSING') . "\n";
        echo "Longitude: " . ($addr['longitude'] ?: '❌ MISSING') . "\n";
        echo "Default: " . ($addr['is_default'] ? 'YES' : 'No') . "\n";

        // Check if this address would work for shipping
        $hasPostal = !empty($addr['postal_code']);
        $hasCoords = !empty($addr['latitude']) && !empty($addr['longitude']);

        if ($hasPostal || $hasCoords) {
            echo "✅ VALID FOR SHIPPING\n";
        } else {
            echo "❌ NOT VALID - MISSING POSTAL CODE AND GPS\n";
        }

        echo "------------------------\n\n";
    }
}

// Check what data is sent from frontend
echo "\n=== EXPECTED DATA FORMAT ===\n";
echo "Frontend should send:\n";
echo "{\n";
echo "  latitude: '-6.200000',\n";
echo "  longitude: '106.816666',\n";
echo "  postal_code: '10110', // Jakarta\n";
echo "  items: [...]\n";
echo "}\n\n";

echo "Backend needs at least ONE of:\n";
echo "- postal_code\n";
echo "- latitude + longitude\n";
echo "- area_id\n";
?>
