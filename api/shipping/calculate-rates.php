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

    // Format items for Biteship API (ULTRA CLEAN - no HTML, no special chars)
    $formatted_items = [];
    foreach ($items as $item) {
        // AGGRESSIVE cleaning: Strip ALL HTML tags AND entities
        $product_name = $item['name'] ?? 'Product';
        $product_name = strip_tags($product_name); // Remove HTML tags
        $product_name = html_entity_decode($product_name, ENT_QUOTES | ENT_HTML5, 'UTF-8'); // Decode entities
        $product_name = preg_replace('/[^\w\s\-]/u', '', $product_name); // Remove special chars except space, dash, underscore
        $product_name = trim($product_name); // Remove extra spaces

        // Fallback if name is empty after cleaning
        if (empty($product_name)) {
            $product_name = 'Fashion Item';
        }

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

    // Get rates with formatted items (ULTRA CLEAN!)
    $result = $client->getRates($origin, $destination, $formatted_items, $courierCodes);

    error_log("Biteship API Response: " . json_encode($result));
    error_log("Biteship API Request Items: " . json_encode($formatted_items));

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

            // Use minimum 3km if distance is 0 or very small (same location)
            $calcDistance = $distance > 0 ? $distance : 3;

            // Check if same area (Sumut region - postal codes starting with 20, 21, 22)
            $isSameRegion = false;
            if (!empty($destPostal) && strlen($destPostal) >= 2) {
                $destPrefix = substr($destPostal, 0, 2);
                $isSameRegion = in_array($destPrefix, ['20', '21', '22']);
            }

            // Offer local delivery options for short distances or same region
            if (($distance >= 0 && $distance <= 100) || $isSameRegion) {
                error_log("Local delivery detected: Distance = {$distance}km (calc: {$calcDistance}km), Origin Postal: $originPostal, Dest Postal: $destPostal");

                // === SMART PRICING SYSTEM WITH MARKUP ===
                // Real cost calculation + markup untuk profit

                // OPTION 1: GOSEND EXPRESS (Fast but expensive)
                // Real GoSend: Base Rp 20,000 (8km) + Rp 2,500/km
                // Our Price: Real cost + 20% markup (competitive pricing!)
                if ($calcDistance <= 30) {
                    // Calculate REAL GoSend cost
                    $realGoSendCost = 20000; // Base 8km
                    if ($calcDistance > 8) {
                        $realGoSendCost += ($calcDistance - 8) * 2500; // Rp 2,500/km after 8km
                    }

                    // Add 20% markup for profit (lowered from 30% for competitive pricing)
                    $goSendPrice = $realGoSendCost * 1.20;

                    // Round to nearest 1000
                    $goSendPrice = ceil($goSendPrice / 1000) * 1000;

                    $rates[] = [
                        'courier_company' => 'gosend',
                        'courier_name' => '⚡ GoSend Express',
                        'courier_service_name' => 'Instant (1-2 Jam)',
                        'rate_id' => 'gosend-instant',
                        'price' => (int)$goSendPrice,
                        'duration' => 'Langsung sampai 1-2 jam',
                        'description' => 'Pengiriman super cepat via GoSend • Lacak real-time',
                        'available' => true,
                        'distance_km' => round($calcDistance, 1),
                        'icon' => '⚡',
                        'badge' => 'TERCEPAT'
                    ];
                }

                // OPTION 2: GRAB EXPRESS (Cheaper but slower)
                // Real Grab: Base Rp 13,000 (6km) + Rp 1,500/km
                // Our Price: Real cost + 20% markup (competitive pricing!)
                if ($calcDistance <= 30) {
                    // Calculate REAL Grab cost
                    $realGrabCost = 13000; // Base 6km
                    if ($calcDistance > 6) {
                        $realGrabCost += ($calcDistance - 6) * 1500; // Rp 1,500/km after 6km (competitive rate)
                    }

                    // Add 20% markup for profit (lowered from 25% for competitive pricing)
                    $grabPrice = $realGrabCost * 1.20;

                    // Round to nearest 1000
                    $grabPrice = ceil($grabPrice / 1000) * 1000;

                    $rates[] = [
                        'courier_company' => 'grab',
                        'courier_name' => '🚗 Grab Express',
                        'courier_service_name' => 'Same Day (3-6 Jam)',
                        'rate_id' => 'grab-sameday',
                        'price' => (int)$grabPrice,
                        'duration' => 'Same day delivery (3-6 jam)',
                        'description' => 'Pengiriman hemat via Grab Express • Lacak real-time',
                        'available' => true,
                        'distance_km' => round($calcDistance, 1),
                        'icon' => '🚗',
                        'badge' => 'HEMAT'
                    ];
                }

                // OPTION 3: JNT REGULAR (Cheapest option)
                // Untuk customer yang mau lebih murah tapi ga urgent
                if ($calcDistance <= 50) {
                    $jntPrice = 8000 + ($calcDistance * 400);
                    $jntPrice = ceil($jntPrice / 1000) * 1000;

                    $rates[] = [
                        'courier_company' => 'local-jnt',
                        'courier_name' => '📦 JNT Regular',
                        'courier_service_name' => 'Regular (1-2 Hari)',
                        'rate_id' => 'jnt-regular',
                        'price' => (int)$jntPrice,
                        'duration' => '1-2 hari kerja',
                        'description' => 'Pengiriman ekonomis via JNT lokal',
                        'available' => true,
                        'distance_km' => round($calcDistance, 1),
                        'icon' => '📦',
                        'badge' => 'EKONOMIS'
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
            'message' => count($rates) > 0 ? 'Rates available' : 'No couriers available for this area',
            'debug' => [
                'origin' => $origin,
                'destination' => $destination,
                'biteship_response' => $result
            ]
        ]);
    } else {
        // Biteship API returned error
        $errorMessage = $result['error'] ?? 'Failed to get shipping rates from Biteship';
        error_log("Biteship API Error: " . $errorMessage);

        // Return detailed error to help debug
        echo json_encode([
            'success' => false,
            'error' => $errorMessage,
            'rates' => [],
            'debug' => [
                'biteship_error' => $result,
                'origin' => $origin,
                'destination' => $destination,
                'items_sent' => $formatted_items
            ]
        ]);
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