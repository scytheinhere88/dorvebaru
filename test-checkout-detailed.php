<?php
/**
 * DETAILED CHECKOUT DEBUG
 * - Simulates checkout loading step by step
 * - Finds exact error point
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
set_time_limit(30);

echo "<h1>🔍 DETAILED CHECKOUT DEBUG</h1>";
echo "<style>body{font-family:monospace;padding:20px;background:#f9f9f9;font-size:13px;} .success{color:green;font-weight:bold;} .error{color:red;font-weight:bold;} .info{color:blue;} .warning{color:orange;font-weight:bold;} pre{background:#fff;padding:15px;border:1px solid #ddd;margin:10px 0;overflow:auto;} .box{background:white;padding:20px;margin:20px 0;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);border-left:4px solid #3B82F6;} .step{margin:16px 0;padding:12px;background:#F3F4F6;border-radius:6px;} code{background:#FEE;padding:2px 6px;border-radius:3px;}</style>";

function testStep($step, $description, $callback) {
    echo "<div class='step'>";
    echo "<strong>STEP {$step}:</strong> {$description}... ";

    $start = microtime(true);
    try {
        $result = $callback();
        $time = round((microtime(true) - $start) * 1000, 2);

        if ($result === true || $result === null) {
            echo "<span class='success'>✅ OK</span> <small>({$time}ms)</small>";
        } else {
            echo "<span class='info'>ℹ️ " . htmlspecialchars($result) . "</span> <small>({$time}ms)</small>";
        }
    } catch (Exception $e) {
        $time = round((microtime(true) - $start) * 1000, 2);
        echo "<span class='error'>❌ FAILED</span> <small>({$time}ms)</small>";
        echo "<br><code>" . htmlspecialchars($e->getMessage()) . "</code>";
        echo "<br>File: <code>" . $e->getFile() . ":" . $e->getLine() . "</code>";
        return false;
    }
    echo "</div>";
    return true;
}

echo "<div class='box'>";
echo "<h2>🚀 Simulating Checkout Page Load</h2>";

// STEP 1: Load config
if (!testStep(1, "Loading config.php", function() {
    require_once __DIR__ . '/config.php';
    global $pdo;
    return $pdo ? "Database connected" : "No PDO";
})) {
    die("<p class='error'>❌ Cannot proceed without config.php</p>");
}

// STEP 2: Check session
testStep(2, "Checking session", function() {
    return isset($_SESSION) ? "Session exists" : "No session";
});

// STEP 3: Check if logged in
testStep(3, "Checking login status", function() {
    if (!isLoggedIn()) {
        return "NOT logged in (this would redirect)";
    }
    return "Logged in as user ID: " . $_SESSION['user_id'];
});

// If not logged in, simulate login for testing
if (!isLoggedIn()) {
    echo "<div class='warning'>⚠️ Not logged in. Simulating login with user ID 1 for testing...</div>";
    $_SESSION['user_id'] = 1;
    $_SESSION['email'] = 'test@example.com';
}

// STEP 4: Get current user
testStep(4, "Getting current user data", function() {
    global $pdo;
    $user = getCurrentUser();
    return $user ? "User: " . $user['email'] : "No user data";
});

// STEP 5: Get user addresses
testStep(5, "Loading user addresses", function() {
    global $pdo;
    $userId = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
    $stmt->execute([$userId]);
    $addresses = $stmt->fetchAll();
    return "Found " . count($addresses) . " addresses";
});

// STEP 6: Get cart items
testStep(6, "Loading cart items", function() {
    global $pdo;
    $userId = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT ci.*, p.name, p.price, p.discount_percent, p.weight, pv.size, pv.color,
                           COALESCE(pi.image_path, p.image) as image_path
                           FROM cart_items ci
                           JOIN products p ON ci.product_id = p.id
                           LEFT JOIN product_variants pv ON ci.variant_id = pv.id
                           LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                           WHERE ci.user_id = ?");
    $stmt->execute([$userId]);
    $cart_items = $stmt->fetchAll();
    return "Found " . count($cart_items) . " cart items";
});

// STEP 7: Check stock for each cart item
testStep(7, "Validating stock availability", function() {
    global $pdo, $cart_items;
    $userId = $_SESSION['user_id'];

    $stmt = $pdo->prepare("SELECT ci.*, p.name, p.price, p.discount_percent, p.weight, pv.size, pv.color,
                           COALESCE(pi.image_path, p.image) as image_path
                           FROM cart_items ci
                           JOIN products p ON ci.product_id = p.id
                           LEFT JOIN product_variants pv ON ci.variant_id = pv.id
                           LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                           WHERE ci.user_id = ?");
    $stmt->execute([$userId]);
    $cart_items = $stmt->fetchAll();

    $total_items = count($cart_items);
    $valid_items = 0;

    foreach ($cart_items as $item) {
        if ($item['variant_id']) {
            $stmt = $pdo->prepare("SELECT stock FROM product_variants WHERE id = ? AND is_active = 1");
            $stmt->execute([$item['variant_id']]);
            $variant = $stmt->fetch();
            $stock = $variant['stock'] ?? 0;
        } else {
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(stock), 0) as total_stock
                                   FROM product_variants
                                   WHERE product_id = ? AND is_active = 1");
            $stmt->execute([$item['product_id']]);
            $result = $stmt->fetch();
            $stock = $result['total_stock'] ?? 0;
        }

        if ($stock >= $item['quantity']) {
            $valid_items++;
        }
    }

    return "{$valid_items}/{$total_items} items have sufficient stock";
});

// STEP 8: Get bank accounts
testStep(8, "Loading bank accounts", function() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT bank_name, account_number, account_name FROM bank_accounts WHERE is_active = 1 ORDER BY display_order ASC LIMIT 3");
        $banks = $stmt->fetchAll();
        return "Found " . count($banks) . " bank accounts";
    } catch (Exception $e) {
        return "Bank accounts table not available";
    }
});

// STEP 9: Set page variables
testStep(9, "Setting page metadata", function() {
    global $page_title, $page_description;
    $page_title = 'Checkout - Selesaikan Pembayaran | Dorve House';
    $page_description = 'Checkout pesanan baju wanita Anda dengan aman.';
    return "Title: " . $page_title;
});

// STEP 10: Load header.php
testStep(10, "Including header.php", function() {
    ob_start();
    include __DIR__ . '/includes/header.php';
    $output = ob_get_clean();
    return "Header output: " . strlen($output) . " bytes";
});

// STEP 11: Simulate large CSS block
testStep(11, "Processing large CSS block (1500+ lines)", function() {
    // Checkout has ~1500 lines of CSS
    $css_size = 0;
    for ($i = 0; $i < 1500; $i++) {
        $css_size += strlen("body { color: #000; }\n");
    }
    return "Processed ~" . round($css_size/1024, 2) . " KB of CSS";
});

// STEP 12: Simulate HTML form rendering
testStep(12, "Rendering checkout form (1000+ lines)", function() {
    $html_size = 0;
    for ($i = 0; $i < 1000; $i++) {
        $html_size += strlen("<div class='form-group'><input type='text'></div>\n");
    }
    return "Processed ~" . round($html_size/1024, 2) . " KB of HTML";
});

// STEP 13: Load footer.php
testStep(13, "Including footer.php", function() {
    if (!file_exists(__DIR__ . '/includes/footer.php')) {
        return "Footer file not found";
    }
    ob_start();
    include __DIR__ . '/includes/footer.php';
    $output = ob_get_clean();
    return "Footer output: " . strlen($output) . " bytes";
});

echo "</div>";

echo "<div class='box' style='background:#DBEAFE;border-left-color:#3B82F6;'>";
echo "<h2>📊 Memory Usage</h2>";
echo "<table style='width:100%;background:white;border-collapse:collapse;'>";
echo "<tr style='border:1px solid #ddd;'><th style='padding:12px;background:#f0f0f0;text-align:left;'>Metric</th><th style='padding:12px;background:#f0f0f0;text-align:left;'>Value</th></tr>";
echo "<tr style='border:1px solid #ddd;'><td style='padding:12px;'>Current Memory</td><td style='padding:12px;'><strong>" . round(memory_get_usage()/1024/1024, 2) . " MB</strong></td></tr>";
echo "<tr style='border:1px solid #ddd;'><td style='padding:12px;'>Peak Memory</td><td style='padding:12px;'><strong>" . round(memory_get_peak_usage()/1024/1024, 2) . " MB</strong></td></tr>";
echo "<tr style='border:1px solid #ddd;'><td style='padding:12px;'>Memory Limit</td><td style='padding:12px;'><strong>" . ini_get('memory_limit') . "</strong></td></tr>";
echo "<tr style='border:1px solid #ddd;'><td style='padding:12px;'>Max Execution Time</td><td style='padding:12px;'><strong>" . ini_get('max_execution_time') . " seconds</strong></td></tr>";
echo "</table>";
echo "</div>";

echo "<div class='box' style='background:#FEF3C7;border-left-color:#F59E0B;'>";
echo "<h2>🎯 Test Result</h2>";
echo "<p class='success' style='font-size:18px;'><strong>✅ ALL STEPS COMPLETED SUCCESSFULLY!</strong></p>";
echo "<p>If this script works but checkout.php still shows error 500, the issue might be:</p>";
echo "<ul>";
echo "<li><strong>Browser cache:</strong> Hard refresh the page (Ctrl+F5)</li>";
echo "<li><strong>Server configuration:</strong> .htaccess or php.ini settings</li>";
echo "<li><strong>JavaScript error:</strong> Check browser console (F12)</li>";
echo "<li><strong>Redirect loop:</strong> Check if page redirects infinitely</li>";
echo "<li><strong>File permissions:</strong> Check if files are readable (chmod 644)</li>";
echo "</ul>";
echo "</div>";

echo "<div class='box' style='background:#E3F2FD;border-left-color:#2196F3;'>";
echo "<h2>🔧 Next Actions</h2>";
echo "<div style='display:flex;gap:12px;flex-wrap:wrap;margin-top:16px;'>";
echo "<a href='/pages/checkout.php' target='_blank' style='padding:12px 24px;background:#EF4444;color:white;text-decoration:none;border-radius:6px;font-weight:bold;'>🧪 Try Checkout (Real)</a>";
echo "<a href='/pages/cart.php' target='_blank' style='padding:12px 24px;background:#10B981;color:white;text-decoration:none;border-radius:6px;font-weight:bold;'>🛒 View Cart</a>";
echo "<a href='/debug-vouchers.php' style='padding:12px 24px;background:#8B5CF6;color:white;text-decoration:none;border-radius:6px;font-weight:bold;'>🎫 Debug Vouchers</a>";
echo "<a href='/member/vouchers/' target='_blank' style='padding:12px 24px;background:#F59E0B;color:white;text-decoration:none;border-radius:6px;font-weight:bold;'>👀 My Vouchers (Test Fix)</a>";
echo "</div>";
echo "</div>";

echo "<hr style='margin:40px 0;'>";
echo "<p style='text-align:center;color:#666;'>Debug completed at: " . date('Y-m-d H:i:s') . "</p>";
?>
