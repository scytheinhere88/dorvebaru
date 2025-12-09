<?php
/**
 * SHIPPING DEBUG TOOL
 * Use this to test and debug shipping calculations
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/BiteshipClient.php';

// Set headers
header('Content-Type: text/html; charset=utf-8');

// Get test parameters
$testLat = $_GET['lat'] ?? '-3.5952'; // Default: Binjai
$testLng = $_GET['lng'] ?? '98.5006';
$testPostal = $_GET['postal'] ?? '20239';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Debug Tool</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 32px; margin-bottom: 10px; }
        .header p { opacity: 0.9; }
        .content { padding: 30px; }
        .section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        .section h2 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 20px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .info-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 2px solid #e9ecef;
        }
        .info-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: #212529;
            word-break: break-all;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
        }
        .status-success {
            background: #d4edda;
            color: #155724;
        }
        .status-error {
            background: #f8d7da;
            color: #721c24;
        }
        .status-warning {
            background: #fff3cd;
            color: #856404;
        }
        pre {
            background: #212529;
            color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.5;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.4);
        }
        .test-form {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 10px;
            margin-top: 15px;
        }
        .test-form input {
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 14px;
        }
        .courier-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .courier-available {
            border-color: #28a745;
        }
        .courier-unavailable {
            border-color: #dc3545;
            opacity: 0.6;
        }
        .courier-info h3 {
            font-size: 16px;
            color: #212529;
            margin-bottom: 5px;
        }
        .courier-info p {
            font-size: 13px;
            color: #6c757d;
        }
        .courier-price {
            font-size: 20px;
            font-weight: 700;
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚚 Shipping Debug Tool</h1>
            <p>Test dan debug Biteship shipping integration</p>
        </div>

        <div class="content">
            <!-- Test Form -->
            <div class="section">
                <h2>🎯 Test Parameters</h2>
                <form method="GET" class="test-form">
                    <div>
                        <div class="info-label">Latitude</div>
                        <input type="text" name="lat" value="<?= htmlspecialchars($testLat) ?>" placeholder="-3.5952">
                    </div>
                    <div>
                        <div class="info-label">Longitude</div>
                        <input type="text" name="lng" value="<?= htmlspecialchars($testLng) ?>" placeholder="98.5006">
                    </div>
                    <div>
                        <div class="info-label">Postal Code</div>
                        <input type="text" name="postal" value="<?= htmlspecialchars($testPostal) ?>" placeholder="20239">
                    </div>
                    <button type="submit" class="btn">Test</button>
                </form>
            </div>

            <?php
            // Step 1: Check Biteship Configuration
            echo '<div class="section">';
            echo '<h2>1️⃣ Biteship Configuration</h2>';

            try {
                $config = BiteshipConfig::load();

                if (!empty($config['api_key'])) {
                    echo '<span class="status-badge status-success">✅ API Key Configured</span><br><br>';
                    echo '<div class="info-item">';
                    echo '<div class="info-label">API Key (masked)</div>';
                    echo '<div class="info-value">' . substr($config['api_key'], 0, 10) . '...' . substr($config['api_key'], -5) . '</div>';
                    echo '</div>';
                } else {
                    echo '<span class="status-badge status-error">❌ API Key Not Found</span><br><br>';
                    echo '<p>Run: <code>configure-biteship.php</code></p>';
                }
            } catch (Exception $e) {
                echo '<span class="status-badge status-error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</span>';
            }
            echo '</div>';

            // Step 2: Check Store Settings
            echo '<div class="section">';
            echo '<h2>2️⃣ Store Origin Settings</h2>';

            $storeSettings = [];

            // Try system_settings first
            try {
                $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE 'store_%'");
                while ($row = $stmt->fetch()) {
                    $storeSettings[$row['setting_key']] = $row['setting_value'];
                }
            } catch (Exception $e) {
                // Try site_settings
                try {
                    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'store_%'");
                    while ($row = $stmt->fetch()) {
                        $storeSettings[$row['setting_key']] = $row['setting_value'];
                    }
                } catch (Exception $e2) {
                    echo '<span class="status-badge status-error">❌ No settings table found</span>';
                }
            }

            if (!empty($storeSettings)) {
                echo '<span class="status-badge status-success">✅ Store Settings Found</span><br>';
                echo '<div class="info-grid">';
                foreach ($storeSettings as $key => $value) {
                    echo '<div class="info-item">';
                    echo '<div class="info-label">' . htmlspecialchars($key) . '</div>';
                    echo '<div class="info-value">' . htmlspecialchars($value) . '</div>';
                    echo '</div>';
                }
                echo '</div>';
            } else {
                echo '<span class="status-badge status-warning">⚠️ Using default settings (Binjai)</span>';
            }
            echo '</div>';

            // Step 3: Test Sample Cart Items
            echo '<div class="section">';
            echo '<h2>3️⃣ Sample Cart Items</h2>';

            $sampleItems = [
                [
                    'name' => 'Test Product <strong>With HTML</strong>',
                    'price' => 266750,
                    'discount_percent' => 0,
                    'qty' => 1,
                    'weight' => 500 // grams
                ]
            ];

            echo '<pre>' . json_encode($sampleItems, JSON_PRETTY_PRINT) . '</pre>';

            // Clean items (what gets sent to API)
            $cleanItems = [];
            foreach ($sampleItems as $item) {
                $cleanItems[] = [
                    'name' => strip_tags($item['name']),
                    'value' => (int)$item['price'],
                    'weight' => (int)$item['weight'],
                    'quantity' => (int)$item['qty']
                ];
            }

            echo '<p style="margin-top: 15px;"><strong>After Cleaning (sent to API):</strong></p>';
            echo '<pre>' . json_encode($cleanItems, JSON_PRETTY_PRINT) . '</pre>';
            echo '</div>';

            // Step 4: Test API Call
            echo '<div class="section">';
            echo '<h2>4️⃣ API Test Results</h2>';

            try {
                $client = new BiteshipClient();

                $origin = [
                    'postal_code' => $storeSettings['store_postal_code'] ?? '20719'
                ];

                $destination = [
                    'latitude' => $testLat,
                    'longitude' => $testLng
                ];

                if (!empty($testPostal)) {
                    $destination['postal_code'] = $testPostal;
                }

                $courierCodes = 'jne,jnt,sicepat,anteraja,idexpress,ninja';

                echo '<p><strong>Request Details:</strong></p>';
                echo '<pre>' . json_encode([
                    'origin' => $origin,
                    'destination' => $destination,
                    'items' => $cleanItems,
                    'couriers' => $courierCodes
                ], JSON_PRETTY_PRINT) . '</pre>';

                echo '<p style="margin-top: 15px;"><strong>Calling Biteship API...</strong></p>';

                $result = $client->getRates($origin, $destination, $cleanItems, $courierCodes);

                // Calculate distance
                $originLat = -3.5952; // Binjai
                $originLng = 98.5006;
                $destLat = floatval($testLat);
                $destLng = floatval($testLng);

                $distance = 0;
                if ($destLat != 0 && $destLng != 0) {
                    $earthRadius = 6371; // km
                    $latDiff = deg2rad($destLat - $originLat);
                    $lngDiff = deg2rad($destLng - $originLng);
                    $a = sin($latDiff / 2) * sin($latDiff / 2) +
                         cos(deg2rad($originLat)) * cos(deg2rad($destLat)) *
                         sin($lngDiff / 2) * sin($lngDiff / 2);
                    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
                    $distance = $earthRadius * $c;
                }

                echo '<p style="margin-top: 15px;"><strong>📏 Calculated Distance: ' . round($distance, 1) . ' km</strong></p>';

                if ($result['success']) {
                    echo '<span class="status-badge status-success">✅ API Call Successful</span><br><br>';

                    $pricing = $result['data']['pricing'] ?? [];

                    $availableCount = 0;
                    $unavailableCount = 0;

                    foreach ($pricing as $rate) {
                        $price = floatval($rate['price'] ?? 0);
                        $isAvailable = $price > 0;

                        if ($isAvailable) {
                            $availableCount++;
                        } else {
                            $unavailableCount++;
                        }

                        echo '<div class="courier-card ' . ($isAvailable ? 'courier-available' : 'courier-unavailable') . '">';
                        echo '<div class="courier-info">';
                        echo '<h3>' . htmlspecialchars($rate['courier_name'] ?? 'N/A') . '</h3>';
                        echo '<p>' . htmlspecialchars($rate['courier_service_name'] ?? '') . '</p>';
                        echo '<p style="font-size: 12px; margin-top: 5px;">' . htmlspecialchars($rate['description'] ?? '') . '</p>';
                        echo '<p style="font-size: 12px; color: #667eea; margin-top: 3px;">⏱️ ' . htmlspecialchars($rate['duration'] ?? 'N/A') . '</p>';
                        echo '</div>';
                        echo '<div class="courier-price">';
                        if ($isAvailable) {
                            echo 'Rp ' . number_format($price, 0, ',', '.');
                        } else {
                            echo '<span style="color: #dc3545;">N/A</span>';
                        }
                        echo '</div>';
                        echo '</div>';
                    }

                    echo '<p style="margin-top: 15px;"><strong>Summary:</strong></p>';
                    echo '<p>✅ Available: ' . $availableCount . ' | ❌ Unavailable: ' . $unavailableCount . '</p>';

                    if ($availableCount === 0) {
                        echo '<br><span class="status-badge status-warning">⚠️ No couriers available for this destination</span>';
                        echo '<p style="margin-top: 10px;">Possible reasons:</p>';
                        echo '<ul style="margin-left: 20px; margin-top: 10px;">';
                        echo '<li>Destination too far or not serviceable</li>';
                        echo '<li>Postal code incorrect</li>';
                        echo '<li>Couriers don\'t service this area</li>';
                        echo '</ul>';
                    }

                    echo '<p style="margin-top: 20px;"><strong>Full API Response:</strong></p>';
                    echo '<pre>' . json_encode($result['data'], JSON_PRETTY_PRINT) . '</pre>';

                } else {
                    echo '<span class="status-badge status-error">❌ Biteship API Failed</span><br><br>';
                    echo '<p><strong>Error:</strong> ' . htmlspecialchars($result['error']) . '</p>';

                    // Check for local delivery fallback
                    echo '<br><hr style="margin: 20px 0; border: 1px dashed #ddd;"><br>';
                    echo '<h3 style="color: #667eea;">🚚 Checking Local Delivery Options...</h3>';

                    $originPostal = '20719';
                    $destPostal = $testPostal;

                    // Check if same region (Sumut: 20xxx, 21xxx, 22xxx)
                    $isSameRegion = false;
                    if (!empty($destPostal) && strlen($destPostal) >= 2) {
                        $destPrefix = substr($destPostal, 0, 2);
                        $isSameRegion = in_array($destPrefix, ['20', '21', '22']);
                    }

                    echo '<p>📍 <strong>Origin Postal:</strong> ' . $originPostal . ' (Binjai)</p>';
                    echo '<p>📍 <strong>Destination Postal:</strong> ' . $destPostal . '</p>';
                    echo '<p>📏 <strong>Distance:</strong> ' . round($distance, 1) . ' km</p>';
                    echo '<p>🗺️ <strong>Same Region (Sumut):</strong> ' . ($isSameRegion ? '✅ Yes' : '❌ No') . '</p>';

                    $localRates = [];

                    // Use minimum 3km if distance is 0 or very small (same location)
                    $calcDistance = $distance > 0 ? $distance : 3;

                    // Offer local delivery options for short distances or same region
                    if (($distance >= 0 && $distance <= 100) || $isSameRegion) {
                        echo '<br><span class="status-badge status-success">✅ Local Delivery Available!</span><br><br>';

                        if ($distance == 0 || $distance < 1) {
                            echo '<p style="color: #856404; background: #fff3cd; padding: 10px; border-radius: 6px;">ℹ️ Distance is 0 or very small (same location coordinates). Using minimum 3km for pricing calculation.</p><br>';
                        }

                        // OPTION 1: INSTANT COURIER (≤25km)
                        if ($calcDistance <= 25) {
                            $instantPrice = 15000 + ($calcDistance * 1000);
                            $localRates[] = [
                                'courier_company' => 'instant',
                                'courier_name' => '🚀 Kurir Instan',
                                'courier_service_name' => 'Same Day (Express)',
                                'rate_id' => 'instant-sameday',
                                'price' => (int)$instantPrice,
                                'duration' => 'Hari ini (3-6 jam)',
                                'description' => 'Pengiriman instant menggunakan kurir lokal (Grab/GoSend style)',
                                'available' => true,
                                'distance_km' => round($calcDistance, 1)
                            ];
                        }

                        // OPTION 2: JNT SAME DAY (≤50km)
                        if ($calcDistance <= 50) {
                            $regularPrice = 10000 + ($calcDistance * 500);
                            $localRates[] = [
                                'courier_company' => 'local-jnt',
                                'courier_name' => '📦 JNT Lokal',
                                'courier_service_name' => 'Same Day Regular',
                                'rate_id' => 'jnt-sameday',
                                'price' => (int)$regularPrice,
                                'duration' => '1 hari kerja',
                                'description' => 'Pengiriman same day menggunakan JNT atau kurir lokal',
                                'available' => true,
                                'distance_km' => round($calcDistance, 1)
                            ];
                        }

                        // OPTION 3: ECONOMY (≤100km)
                        if ($calcDistance <= 100) {
                            $economyPrice = 8000 + ($calcDistance * 300);
                            $localRates[] = [
                                'courier_company' => 'local-economy',
                                'courier_name' => '🏪 Kurir Lokal',
                                'courier_service_name' => 'Regular (Ekonomis)',
                                'rate_id' => 'local-economy',
                                'price' => (int)$economyPrice,
                                'duration' => '1-2 hari kerja',
                                'description' => 'Pengiriman ekonomis untuk area Sumut (Medan, Binjai, Deli Serdang, dll)',
                                'available' => true,
                                'distance_km' => round($calcDistance, 1)
                            ];
                        }

                        // FALLBACK
                        if (count($localRates) === 0 && $isSameRegion) {
                            $localRates[] = [
                                'courier_company' => 'local',
                                'courier_name' => '🚚 Pengiriman Lokal',
                                'courier_service_name' => 'Regular Delivery',
                                'rate_id' => 'flat-rate-local',
                                'price' => 12000,
                                'duration' => '1-2 hari kerja',
                                'description' => 'Pengiriman lokal area Sumatera Utara',
                                'available' => true
                            ];
                        }

                        // Display local rates
                        if (count($localRates) > 0) {
                            foreach ($localRates as $rate) {
                                echo '<div class="courier-card courier-available" style="border-left: 4px solid #28a745;">';
                                echo '<div class="courier-info">';
                                echo '<h3>' . htmlspecialchars($rate['courier_name']) . '</h3>';
                                echo '<p>' . htmlspecialchars($rate['courier_service_name']) . '</p>';
                                echo '<p style="font-size: 12px; margin-top: 5px;">' . htmlspecialchars($rate['description']) . '</p>';
                                if (isset($rate['distance_km'])) {
                                    echo '<p style="font-size: 12px; color: #28a745; margin-top: 3px;">📏 ' . $rate['distance_km'] . ' km</p>';
                                }
                                echo '<p style="font-size: 12px; color: #667eea; margin-top: 3px;">⏱️ ' . htmlspecialchars($rate['duration']) . '</p>';
                                echo '</div>';
                                echo '<div class="courier-price">';
                                echo 'Rp ' . number_format($rate['price'], 0, ',', '.');
                                echo '</div>';
                                echo '</div>';
                            }

                            echo '<p style="margin-top: 15px;"><strong>Summary:</strong></p>';
                            echo '<p>✅ <strong>' . count($localRates) . ' Local Delivery Options Available!</strong></p>';

                            echo '<div style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px; padding: 15px; margin-top: 15px;">';
                            echo '<h4 style="color: #155724; margin-bottom: 10px;">🎉 SUCCESS!</h4>';
                            echo '<p style="color: #155724;">Biteship couriers tidak available untuk jarak dekat, tapi sistem otomatis offer <strong>' . count($localRates) . ' local delivery options</strong>!</p>';
                            echo '<p style="color: #155724; margin-top: 10px;">✅ Customer tetap bisa checkout dengan pilihan kurir lokal!</p>';
                            echo '</div>';
                        }
                    } else {
                        echo '<br><span class="status-badge status-warning">⚠️ No Local Delivery Options</span><br><br>';
                        echo '<p>Destination too far (> 100km) and not in Sumut region.</p>';
                        echo '<p>Customer needs to use different address or wait for Biteship courier activation.</p>';
                    }

                    echo '<br><p style="margin-top: 20px;"><strong>Biteship API Response:</strong></p>';
                    echo '<pre>' . json_encode($result, JSON_PRETTY_PRINT) . '</pre>';
                }

            } catch (Exception $e) {
                echo '<span class="status-badge status-error">❌ Exception: ' . htmlspecialchars($e->getMessage()) . '</span>';
                echo '<pre>';
                echo 'File: ' . $e->getFile() . "\n";
                echo 'Line: ' . $e->getLine() . "\n";
                echo 'Trace: ' . $e->getTraceAsString();
                echo '</pre>';
            }

            echo '</div>';

            // Step 5: Recommendations
            echo '<div class="section">';
            echo '<h2>5️⃣ Recommendations</h2>';
            echo '<ul style="margin-left: 20px; line-height: 2;">';
            echo '<li>✅ Make sure <code>configure-biteship.php</code> has been run</li>';
            echo '<li>✅ Check that API key is valid in Biteship dashboard</li>';
            echo '<li>✅ Verify store postal code is correct</li>';
            echo '<li>✅ Test with different destination coordinates</li>';
            echo '<li>✅ Ensure product weights are in grams (500g minimum)</li>';
            echo '</ul>';
            echo '</div>';
            ?>

            <div style="text-align: center; margin-top: 30px;">
                <a href="/pages/checkout.php" class="btn" style="margin-right: 10px;">Go to Checkout</a>
                <a href="/configure-biteship.php" class="btn">Run Setup</a>
            </div>
        </div>
    </div>
</body>
</html>
