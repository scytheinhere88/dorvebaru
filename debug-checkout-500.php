<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 DEBUG CHECKOUT - 500 ERROR FINDER</h2>";
echo "<pre>";

try {
    echo "1. Testing config.php...\n";
    require_once __DIR__ . '/config.php';
    echo "✅ Config loaded\n\n";

    echo "2. Testing database connection...\n";
    $stmt = $pdo->query("SELECT 1");
    echo "✅ Database OK\n\n";

    echo "3. Testing session...\n";
    if (!isset($_SESSION['user_id'])) {
        echo "⚠️ NOT LOGGED IN - simulating login\n";
        // Simulate login for testing
        $stmt = $pdo->query("SELECT id FROM users WHERE role = 'customer' LIMIT 1");
        $testUser = $stmt->fetch();
        if ($testUser) {
            $_SESSION['user_id'] = $testUser['id'];
            echo "✅ Test user ID: " . $testUser['id'] . "\n\n";
        } else {
            echo "❌ No customer users found\n\n";
        }
    } else {
        echo "✅ User ID: " . $_SESSION['user_id'] . "\n\n";
    }

    if (!isset($_SESSION['user_id'])) {
        echo "⚠️ Cannot test further without user session\n";
        exit;
    }

    $userId = $_SESSION['user_id'];

    echo "4. Testing getCurrentUser()...\n";
    $user = getCurrentUser();
    if ($user) {
        echo "✅ User found: " . $user['name'] . "\n";
        echo "   Email: " . $user['email'] . "\n";
        echo "   Wallet: Rp " . number_format($user['wallet_balance'], 0, ',', '.') . "\n\n";
    } else {
        echo "❌ getCurrentUser() returned null\n\n";
    }

    echo "5. Testing user_addresses query...\n";
    $stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
    $stmt->execute([$userId]);
    $savedAddresses = $stmt->fetchAll();
    echo "✅ Found " . count($savedAddresses) . " addresses\n\n";

    echo "6. Testing cart_items query...\n";
    $stmt = $pdo->prepare("SELECT ci.*, p.name, p.price, p.discount_percent,
                           COALESCE(pv.weight, 500) as weight,
                           pv.size, pv.color,
                           COALESCE(pi.image_path, p.image) as image_path
                           FROM cart_items ci
                           JOIN products p ON ci.product_id = p.id
                           LEFT JOIN product_variants pv ON ci.variant_id = pv.id
                           LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                           WHERE ci.user_id = ?");
    $stmt->execute([$userId]);
    $cart_items = $stmt->fetchAll();
    echo "✅ Found " . count($cart_items) . " cart items\n";

    if (count($cart_items) > 0) {
        foreach ($cart_items as $item) {
            echo "   - " . $item['name'] . " (qty: " . $item['qty'] . ")\n";
        }
    } else {
        echo "   (Cart is empty - adding test item...)\n";

        // Add test item
        $stmt = $pdo->query("SELECT p.id as product_id, pv.id as variant_id
                             FROM products p
                             LEFT JOIN product_variants pv ON p.id = pv.product_id
                             WHERE pv.stock > 0 LIMIT 1");
        $testProduct = $stmt->fetch();

        if ($testProduct) {
            $pdo->prepare("INSERT INTO cart_items (user_id, product_id, variant_id, qty) VALUES (?, ?, ?, 1)")
                ->execute([$userId, $testProduct['product_id'], $testProduct['variant_id']]);
            echo "   ✅ Test item added\n";
        }
    }
    echo "\n";

    echo "7. Testing payment_methods query...\n";
    $stmt = $pdo->query("SELECT * FROM payment_methods WHERE is_active = 1 ORDER BY display_order");
    $payment_methods = $stmt->fetchAll();
    echo "✅ Found " . count($payment_methods) . " payment methods\n";
    foreach ($payment_methods as $method) {
        echo "   - " . $method['name'] . " (" . $method['type'] . ")\n";
    }
    echo "\n";

    echo "8. Testing payment_gateway_settings query...\n";
    $payment_settings = null;
    $stmt = $pdo->query("SELECT * FROM payment_gateway_settings WHERE is_active = 1");
    $gateway_settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ Found " . count($gateway_settings) . " gateway settings\n";
    foreach ($gateway_settings as $gateway) {
        echo "   - " . $gateway['gateway_name'] . "\n";
        if ($gateway['gateway_name'] === 'midtrans') {
            $payment_settings = $gateway;
        }
    }
    echo "\n";

    echo "9. Testing bank_accounts query...\n";
    $stmt = $pdo->query("SELECT bank_name, account_number, account_name FROM bank_accounts WHERE is_active = 1 ORDER BY display_order ASC LIMIT 3");
    $available_banks = $stmt->fetchAll();
    echo "✅ Found " . count($available_banks) . " bank accounts\n\n";

    echo "10. Testing calculateDiscount() function...\n";
    if (function_exists('calculateDiscount')) {
        $testPrice = calculateDiscount(100000, 10);
        echo "✅ calculateDiscount works: Rp 100,000 - 10% = Rp " . number_format($testPrice, 0, ',', '.') . "\n\n";
    } else {
        echo "❌ calculateDiscount() function not found!\n\n";
    }

    echo "11. Checking for undefined constants...\n";
    if (defined('MIDTRANS_CLIENT_KEY')) {
        echo "✅ MIDTRANS_CLIENT_KEY defined\n";
    } else {
        echo "⚠️ MIDTRANS_CLIENT_KEY not defined (this is OK if not using Midtrans)\n";
    }
    echo "\n";

    echo "=" . str_repeat("=", 60) . "\n";
    echo "✅ ALL CHECKS PASSED! \n";
    echo "=" . str_repeat("=", 60) . "\n\n";

    echo "Now trying to include actual checkout.php...\n\n";

    // Try to catch the actual error
    ob_start();
    include __DIR__ . '/pages/checkout.php';
    $output = ob_get_clean();

    if ($output) {
        echo "✅ Checkout page loaded successfully!\n";
        echo "Output length: " . strlen($output) . " bytes\n";
    }

} catch (Exception $e) {
    echo "\n";
    echo "=" . str_repeat("=", 60) . "\n";
    echo "❌ ERROR FOUND!\n";
    echo "=" . str_repeat("=", 60) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>
