<?php
/**
 * FIX ADMIN PRODUCT IMAGES
 * - Load all binary image files
 * - Update product queries to use product_images table
 * - Diagnostic checks
 */

require_once __DIR__ . '/config.php';

if (!isAdmin()) {
    die('Admin access required');
}

echo "<h1>🖼️ Fix Admin Product Images</h1>";
echo "<style>body{font-family:monospace;padding:20px;background:#f9f9f9;} .success{color:green;font-weight:bold;} .error{color:red;font-weight:bold;} .info{color:blue;} pre{background:#fff;padding:15px;border:1px solid #ddd;margin:10px 0;} table{border-collapse:collapse;width:100%;margin:20px 0;background:white;} th,td{border:1px solid #ddd;padding:12px;text-align:left;} th{background:#f0f0f0;font-weight:bold;} .badge{padding:4px 8px;border-radius:4px;font-size:11px;} .badge-success{background:#d4edda;color:#155724;} .badge-error{background:#f8d7da;color:#721c24;}</style>";

echo "<h2>1️⃣ Checking Binary Image Files...</h2>";

$uploadDir = __DIR__ . '/uploads/products/';
$imageFiles = glob($uploadDir . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);

echo "<p><strong>Found " . count($imageFiles) . " image files</strong></p>";
echo "<table>";
echo "<tr><th>File</th><th>Size</th><th>Status</th><th>Action Needed</th></tr>";

$dummyFiles = [];
$realFiles = [];

foreach ($imageFiles as $file) {
    $filename = basename($file);
    $size = filesize($file);
    $content = file_get_contents($file);

    $isDummy = ($size <= 50 || $content === '[DUMMY FILE CONTENT]');

    echo "<tr>";
    echo "<td><code>{$filename}</code></td>";
    echo "<td>" . number_format($size) . " bytes</td>";

    if ($isDummy) {
        echo "<td><span class='badge badge-error'>DUMMY FILE</span></td>";
        echo "<td class='error'>❌ Need to re-upload real image</td>";
        $dummyFiles[] = $filename;
    } else {
        echo "<td><span class='badge badge-success'>REAL FILE</span></td>";
        echo "<td class='success'>✅ OK</td>";
        $realFiles[] = $filename;
    }
    echo "</tr>";
}
echo "</table>";

echo "<div style='background:white;padding:20px;margin:20px 0;border-left:4px solid #3B82F6;'>";
echo "<h3>📊 Summary:</h3>";
echo "<ul>";
echo "<li><strong>Total files:</strong> " . count($imageFiles) . "</li>";
echo "<li class='success'><strong>Real images:</strong> " . count($realFiles) . "</li>";
echo "<li class='error'><strong>Dummy files:</strong> " . count($dummyFiles) . "</li>";
echo "</ul>";
echo "</div>";

if (count($dummyFiles) > 0) {
    echo "<div style='background:#FFF3CD;padding:20px;margin:20px 0;border-left:4px solid #FFC107;'>";
    echo "<h3>⚠️ ACTION REQUIRED:</h3>";
    echo "<p>You have <strong>" . count($dummyFiles) . " dummy image files</strong> that need to be replaced with real images.</p>";
    echo "<p><strong>How to fix:</strong></p>";
    echo "<ol>";
    echo "<li>Go to <a href='/admin/products/'>Admin → Products</a></li>";
    echo "<li>Click <strong>Edit</strong> on each product</li>";
    echo "<li>Re-upload the product images</li>";
    echo "<li>Save the product</li>";
    echo "</ol>";
    echo "</div>";
}

echo "<h2>2️⃣ Checking Product Images Table...</h2>";

try {
    $stmt = $pdo->query("
        SELECT
            p.id as product_id,
            p.name,
            p.image as old_image_column,
            COUNT(pi.id) as image_count,
            GROUP_CONCAT(pi.image_path SEPARATOR ', ') as new_images
        FROM products p
        LEFT JOIN product_images pi ON p.id = pi.product_id
        GROUP BY p.id
        ORDER BY p.created_at DESC
    ");
    $products = $stmt->fetchAll();

    echo "<table>";
    echo "<tr><th>Product ID</th><th>Name</th><th>Old Image Column</th><th>New Images (Count)</th><th>Status</th></tr>";

    foreach ($products as $product) {
        echo "<tr>";
        echo "<td>#{$product['product_id']}</td>";
        echo "<td>" . htmlspecialchars($product['name']) . "</td>";
        echo "<td><code>" . ($product['old_image_column'] ?: 'NULL') . "</code></td>";
        echo "<td>{$product['image_count']} images";
        if ($product['new_images']) {
            echo "<br><small style='color:#666;'>" . htmlspecialchars($product['new_images']) . "</small>";
        }
        echo "</td>";

        if ($product['image_count'] > 0) {
            echo "<td><span class='badge badge-success'>✅ Has Images</span></td>";
        } else {
            echo "<td><span class='badge badge-error'>❌ No Images</span></td>";
        }
        echo "</tr>";
    }
    echo "</table>";

} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

echo "<h2>3️⃣ Checking Admin Navigation...</h2>";

$adminPages = [
    '/admin/settings/index.php' => 'General Settings',
    '/admin/settings/payment-settings.php' => 'Payment Settings',
    '/admin/settings/bank-accounts.php' => 'Bank Accounts',
    '/admin/integration/error-logs.php' => 'Error & Webhook Logs',
    '/admin/products/index.php' => 'Products',
    '/admin/categories/index.php' => 'Categories',
];

echo "<table>";
echo "<tr><th>Page</th><th>File Exists</th><th>Readable</th><th>Status</th></tr>";

foreach ($adminPages as $path => $name) {
    $fullPath = __DIR__ . $path;
    $exists = file_exists($fullPath);
    $readable = $exists && is_readable($fullPath);

    echo "<tr>";
    echo "<td><strong>{$name}</strong><br><code>{$path}</code></td>";
    echo "<td>" . ($exists ? '<span class="badge badge-success">✅ Yes</span>' : '<span class="badge badge-error">❌ No</span>') . "</td>";
    echo "<td>" . ($readable ? '<span class="badge badge-success">✅ Yes</span>' : '<span class="badge badge-error">❌ No</span>') . "</td>";

    if ($exists && $readable) {
        echo "<td class='success'>✅ OK</td>";
    } else {
        echo "<td class='error'>❌ PROBLEM</td>";
    }
    echo "</tr>";
}
echo "</table>";

echo "<h2>4️⃣ Testing Page Access...</h2>";

echo "<div style='background:white;padding:20px;margin:20px 0;'>";
echo "<p>Click links below to test if pages load correctly:</p>";
echo "<ul style='list-style:none;padding:0;'>";
foreach ($adminPages as $path => $name) {
    echo "<li style='margin:8px 0;'>";
    echo "<a href='{$path}' target='_blank' style='display:inline-block;padding:10px 20px;background:#3B82F6;color:white;text-decoration:none;border-radius:6px;'>";
    echo "🔗 {$name}</a>";
    echo "</li>";
}
echo "</ul>";
echo "</div>";

echo "<h2>5️⃣ Recommendations</h2>";

echo "<div style='background:#E8F5E9;padding:20px;margin:20px 0;border-left:4px solid #4CAF50;'>";
echo "<h3>✅ TO FIX PRODUCT IMAGES:</h3>";
echo "<ol>";
echo "<li><strong>Re-upload all product images</strong> through Admin → Products → Edit</li>";
echo "<li>The system will automatically save them to <code>/uploads/products/</code></li>";
echo "<li>Images will be stored in <code>product_images</code> table</li>";
echo "<li>Each product can have multiple images (gallery)</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background:#E3F2FD;padding:20px;margin:20px 0;border-left:4px solid #2196F3;'>";
echo "<h3>🔧 NEXT STEPS:</h3>";
echo "<ol>";
echo "<li>Test Settings page: <a href='/admin/settings/index.php'>Click Here</a></li>";
echo "<li>Test Error Logs: <a href='/admin/integration/error-logs.php'>Click Here</a></li>";
echo "<li>If any page redirects to dashboard, check browser console for JavaScript errors</li>";
echo "<li>Check browser Network tab to see actual HTTP response</li>";
echo "</ol>";
echo "</div>";

echo "<hr style='margin:40px 0;'>";
echo "<div style='text-align:center;'>";
echo "<a href='/admin/' style='display:inline-block;padding:16px 32px;background:#1A1A1A;color:white;text-decoration:none;border-radius:8px;font-weight:bold;'>← Back to Admin Dashboard</a>";
echo "</div>";
?>
