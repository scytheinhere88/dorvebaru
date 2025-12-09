<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/BiteshipClient.php';

header('Content-Type: application/json');

// Enable error logging
error_log("Calculate Rates API Called at " . date('Y-m-d H:i:s'));

try {
    $input = json_decode(file_get_contents('php://input'), true);
    error_log("Calculate Rates Input: " . json_encode($input));

    // Get destination from input
    $destination = [
        'postal_code' => $input['postal_code'] ?? null,
        'area_id' => $input['area_id'] ?? null,
        'latitude' => $input['latitude'] ?? null,
        'longitude' => $input['longitude'] ?? null
    ];

    // Remove null values
    $destination = array_filter($destination, function($v) { return $v !== null; });

    if (empty($destination)) {
        throw new Exception('No destination information provided (postal_code, area_id, or lat/lng required)');
    }

    // Get items
    $items = $input['items'] ?? [];

    if (empty($items)) {
        throw new Exception('No items provided');
    }

    // Format items for Biteship API (clean format, no HTML)
    $formatted_items = [];
    foreach ($items as $item) {
        // Strip HTML tags from product name
        $product_name = strip_tags($item['name'] ?? 'Product');

        // Calculate price with discount
        $price = floatval($item['price'] ?? 0);
        $discount_percent = floatval($item['discount_percent'] ?? 0);

        if ($discount_percent > 0) {
            $price = $price * (1 - ($discount_percent / 100));
        }

        // Get weight in grams (default 500g if not set)
        // If weight is in kg (< 50), convert to grams
        $weight = floatval($item['weight'] ?? 0.5);
        if ($weight < 50) {
            // Assume it's in kg, convert to grams
            $weight = $weight * 1000;
        }
        $weight = intval($weight);

        // Get quantity
        $quantity = intval($item['qty'] ?? 1);

        $formatted_items[] = [
            'name' => $product_name,
            'value' => (int)$price,
            'weight' => $weight,
            'quantity' => $quantity
        ];
    }

    error_log("Formatted Items: " . json_encode($formatted_items));

    // Get store origin from system_settings (try multiple tables for compatibility)
    $storeSettings = [];

    // Try system_settings first
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE 'store_%'");
        while ($row = $stmt->fetch()) {
            $storeSettings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Exception $e) {
        error_log("Could not query system_settings: " . $e->getMessage());
    }

    // Try site_settings if system_settings is empty
    if (empty($storeSettings)) {
        try {
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'store_%'");
            while ($row = $stmt->fetch()) {
                $storeSettings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            error_log("Could not query site_settings: " . $e->getMessage());
        }
    }

    // Set origin with fallback to Binjai postal code
    $origin = [
        'postal_code' => $storeSettings['store_postal_code'] ?? '20719'
    ];

    error_log("Origin: " . json_encode($origin));
    error_log("Destination: " . json_encode($destination));

    // Initialize Biteship client
    $client = new BiteshipClient();

    // Get courier codes from settings
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'biteship_default_couriers'");
        $stmt->execute();
        $courierCodes = $stmt->fetchColumn();
    } catch (Exception $e) {
        $courierCodes = null;
    }

    if (!$courierCodes) {
        $courierCodes = 'jne,jnt,sicepat,anteraja,idexpress,ninja';
    }

    error_log("Courier Codes: " . $courierCodes);

    // Get rates with formatted items (no HTML!)
    $result = $client->getRates($origin, $destination, $formatted_items, $courierCodes);

    error_log("Biteship API Response: " . json_encode($result));
    
    if ($result['success']) {
        $pricing = $result['data']['pricing'] ?? [];
        
        // Format response - ONLY AVAILABLE COURIERS
        $rates = [];
        $unavailableCouriers = [];
        
        foreach ($pricing as $rate) {
            $price = floatval($rate['price'] ?? 0);
            $courierCompany = $rate['courier_company'] ?? '';
            $courierService = $rate['courier_service_name'] ?? '';
            
            // Only include couriers with valid price (available)
            if ($price > 0 && !empty($courierCompany)) {
                $rates[] = [
                    'courier_company' => $courierCompany,
                    'courier_name' => $rate['courier_name'] ?? '',
                    'courier_service_name' => $courierService,
                    'rate_id' => $rate['rate_id'] ?? '',
                    'price' => $price,
                    'duration' => $rate['duration'] ?? '',
                    'description' => $rate['description'] ?? '',
                    'available' => true
                ];
            } else {
                // Track unavailable couriers for logging
                $unavailableCouriers[] = $courierCompany . ' - ' . $courierService;
            }
        }
        
        // Sort by price (cheapest first)
        usort($rates, function($a, $b) {
            return $a['price'] - $b['price'];
        });

        // If no rates available, check if it's local/nearby delivery and offer alternatives
        if (count($rates) === 0) {
            $originPostal = $storeSettings['store_postal_code'] ?? '20719';
            $destPostal = $destination['postal_code'] ?? '';

            $originLat = floatval($storeSettings['store_latitude'] ?? -3.5952);
            $originLng = floatval($storeSettings['store_longitude'] ?? 98.5006);
            $destLat = floatval($destination['latitude'] ?? 0);
            $destLng = floatval($destination['longitude'] ?? 0);

            // Calculate distance in km using Haversine formula
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

            // Check if same area (Sumut region - postal codes starting with 20, 21, 22)
            $isSameRegion = false;
            if (!empty($destPostal) && strlen($destPostal) >= 2) {
                $destPrefix = substr($destPostal, 0, 2);
                $isSameRegion = in_array($destPrefix, ['20', '21', '22']);
            }

            // Offer local delivery options for short distances or same region
            if ($distance > 0 && $distance <= 100 || $isSameRegion) {
                error_log("Local delivery detected: Distance = {$distance}km, Origin Postal: $originPostal, Dest Postal: $destPostal");

                // OPTION 1: INSTANT COURIER (Grab/GoSend style)
                if ($distance <= 25) {
                    $instantPrice = 15000 + ($distance * 1000); // Base + Rp 1000/km
                    $rates[] = [
                        'courier_company' => 'instant',
                        'courier_name' => 'Kurir Instan',
                        'courier_service_name' => 'Same Day (Express)',
                        'rate_id' => 'instant-sameday',
                        'price' => (int)$instantPrice,
                        'duration' => 'Hari ini (3-6 jam)',
                        'description' => 'Pengiriman instant menggunakan kurir lokal (Grab/GoSend style)',
                        'available' => true,
                        'distance_km' => round($distance, 1)
                    ];
                }

                // OPTION 2: JNT SAME DAY / REGULAR LOCAL
                if ($distance <= 50) {
                    $regularPrice = 10000 + ($distance * 500); // Base + Rp 500/km
                    $rates[] = [
                        'courier_company' => 'local-jnt',
                        'courier_name' => 'JNT Lokal',
                        'courier_service_name' => 'Same Day Regular',
                        'rate_id' => 'jnt-sameday',
                        'price' => (int)$regularPrice,
                        'duration' => '1 hari kerja',
                        'description' => 'Pengiriman same day menggunakan JNT atau kurir lokal',
                        'available' => true,
                        'distance_km' => round($distance, 1)
                    ];
                }

                // OPTION 3: EKONOMIS (1-2 hari)
                if ($distance <= 100) {
                    $economyPrice = 8000 + ($distance * 300); // Base + Rp 300/km
                    $rates[] = [
                        'courier_company' => 'local-economy',
                        'courier_name' => 'Kurir Lokal',
                        'courier_service_name' => 'Regular (Ekonomis)',
                        'rate_id' => 'local-economy',
                        'price' => (int)$economyPrice,
                        'duration' => '1-2 hari kerja',
                        'description' => 'Pengiriman ekonomis untuk area Sumut (Medan, Binjai, Deli Serdang, dll)',
                        'available' => true,
                        'distance_km' => round($distance, 1)
                    ];
                }

                // OPTION 4: FALLBACK - Flat rate if no coordinates
                if (count($rates) === 0 && $isSameRegion) {
                    $rates[] = [
                        'courier_company' => 'local',
                        'courier_name' => 'Pengiriman Lokal',
                        'courier_service_name' => 'Regular Delivery',
                        'rate_id' => 'flat-rate-local',
                        'price' => 12000,
                        'duration' => '1-2 hari kerja',
                        'description' => 'Pengiriman lokal area Sumatera Utara',
                        'available' => true
                    ];
                }

                // Sort by price (cheapest first)
                usort($rates, function($a, $b) {
                    return $a['price'] - $b['price'];
                });
            }
        }

        echo json_encode([
            'success' => true,
            'rates' => $rates,
            'total_available' => count($rates),
            'unavailable_couriers' => $unavailableCouriers, // For debugging
            'message' => count($rates) > 0 ? 'Rates available' : 'No couriers available for this area'
        ]);
    } else {
        throw new Exception($result['error'] ?? 'Failed to get shipping rates');
    }
    
} catch (Exception $e) {
    error_log("Calculate Rates Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}