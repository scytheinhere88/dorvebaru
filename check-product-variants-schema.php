<?php
require_once 'config.php';

echo "<pre>";
echo "Checking product_variants table structure...\n\n";

$stmt = $pdo->query("DESCRIBE product_variants");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Current columns in product_variants:\n";
echo "=====================================\n";
foreach ($columns as $col) {
    echo $col['Field'] . " - " . $col['Type'] . "\n";
}

echo "\n\nChecking products table for weight column...\n";
$stmt = $pdo->query("DESCRIBE products");
$productCols = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Current columns in products:\n";
echo "============================\n";
foreach ($productCols as $col) {
    echo $col['Field'] . " - " . $col['Type'] . "\n";
}

echo "</pre>";
?>
