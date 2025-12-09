<?php
require_once __DIR__ . '/config.php';

echo "=== TEST SHIPPING FIX ===\n\n";

// Test coordinates from user's address
$testAddresses = [
    [
        'name' => 'Binjai Address (User)',
        'lat' => 3.68249196,
        'lng' => 98.44815547,
        'postal' => null
    ],
    [
        'name' => 'Medan Address',
        'lat' => 3.5952,
        'lng' => 98.6722,
        'postal' => '20111'
    ],
    [
        'name' => 'Jakarta Address',
        'lat' => -6.2088,
        'lng' => 106.8456,
        'postal' => '10110'
    ]
];

// Store origin (Binjai)
$originLat = 3.5952;
$originLng = 98.5006;

echo "Store Origin: $originLat, $originLng (Binjai)\n\n";

foreach ($testAddresses as $addr) {
    echo "Testing: {$addr['name']}\n";
    echo "  Coordinates: {$addr['lat']}, {$addr['lng']}\n";
    echo "  Postal: " . ($addr['postal'] ?: 'None') . "\n";

    // Calculate distance using Haversine
    $earthRadius = 6371; // km
    $latDiff = deg2rad($addr['lat'] - $originLat);
    $lngDiff = deg2rad($addr['lng'] - $originLng);
    $a = sin($latDiff / 2) * sin($latDiff / 2) +
         cos(deg2rad($originLat)) * cos(deg2rad($addr['lat'])) *
         sin($lngDiff / 2) * sin($lngDiff / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $distance = $earthRadius * $c;

    echo "  Distance: " . round($distance, 2) . " km\n";

    // Check if local delivery would trigger
    $isSameRegion = false;
    if (!empty($addr['postal']) && strlen($addr['postal']) >= 2) {
        $prefix = substr($addr['postal'], 0, 2);
        $isSameRegion = in_array($prefix, ['20', '21', '22']);
    }

    $localDeliveryOK = ($distance >= 0 && $distance <= 100) || $isSameRegion;

    echo "  Same Region: " . ($isSameRegion ? 'YES' : 'NO') . "\n";
    echo "  Local Delivery: " . ($localDeliveryOK ? '✅ YES' : '❌ NO') . "\n";
    echo "  Reason: ";

    if ($distance <= 100) {
        echo "Distance <= 100km\n";
    } elseif ($isSameRegion) {
        echo "Same region (Sumut)\n";
    } else {
        echo "Too far AND different region\n";
    }

    echo "\n";
}

echo "\n=== EXPECTED RESULTS ===\n";
echo "Binjai Address: ✅ Should show local delivery (GoSend, Grab, JNT)\n";
echo "Medan Address: ✅ Should show local delivery (same region)\n";
echo "Jakarta Address: ✅ Should show national couriers from Biteship\n";
?>
