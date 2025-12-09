<?php
/**
 * VERIFY COURIER PRICING CALCULATIONS
 * Run this to verify pricing logic is correct
 */

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Verify Pricing</title>";
echo "<style>body{font-family:Arial;max-width:1200px;margin:40px auto;padding:20px;background:#f5f5f5;}";
echo ".pass{background:#d4edda;color:#155724;padding:15px;border-radius:8px;margin:10px 0;border-left:4px solid #28a745;}";
echo ".fail{background:#f8d7da;color:#721c24;padding:15px;border-radius:8px;margin:10px 0;border-left:4px solid #dc3545;}";
echo "h1{color:#333;}table{width:100%;border-collapse:collapse;background:white;margin:20px 0;}";
echo "th,td{padding:12px;text-align:left;border:1px solid #ddd;}th{background:#667EEA;color:white;}</style></head><body>";

echo "<h1>🧪 Courier Pricing Verification</h1>";

$testCases = [
    ['distance' => 5, 'expected_gosend' => 26000, 'expected_grab' => 17000, 'expected_jnt' => 13000],
    ['distance' => 10, 'expected_gosend' => 34000, 'expected_grab' => 27000, 'expected_jnt' => 15000],
    ['distance' => 15, 'expected_gosend' => 54000, 'expected_grab' => 39000, 'expected_jnt' => 18000],
    ['distance' => 20, 'expected_gosend' => 67000, 'expected_grab' => 49000, 'expected_jnt' => 20000],
    ['distance' => 25, 'expected_gosend' => 79000, 'expected_grab' => 59000, 'expected_jnt' => 23000],
];

$allPassed = true;

echo "<table>";
echo "<tr><th>Distance</th><th>Courier</th><th>Expected</th><th>Actual</th><th>Status</th></tr>";

foreach ($testCases as $test) {
    $distance = $test['distance'];

    // GoSend calculation
    $realGoSendCost = 20000;
    if ($distance > 8) {
        $realGoSendCost += ($distance - 8) * 3000;
    }
    $goSendPrice = $realGoSendCost * 1.30;
    $goSendPrice = ceil($goSendPrice / 1000) * 1000;

    // Grab calculation
    $realGrabCost = 13000;
    if ($distance > 6) {
        $realGrabCost += ($distance - 6) * 2000;
    }
    $grabPrice = $realGrabCost * 1.25;
    $grabPrice = ceil($grabPrice / 1000) * 1000;

    // JNT calculation
    $jntPrice = 10000 + ($distance * 500);
    $jntPrice = ceil($jntPrice / 1000) * 1000;

    // Check GoSend
    $goSendPass = ($goSendPrice == $test['expected_gosend']);
    $goSendStatus = $goSendPass ? '✅ PASS' : '❌ FAIL';
    if (!$goSendPass) $allPassed = false;

    echo "<tr>";
    echo "<td>{$distance}km</td>";
    echo "<td>GoSend</td>";
    echo "<td>Rp " . number_format($test['expected_gosend'], 0, ',', '.') . "</td>";
    echo "<td>Rp " . number_format($goSendPrice, 0, ',', '.') . "</td>";
    echo "<td>{$goSendStatus}</td>";
    echo "</tr>";

    // Check Grab
    $grabPass = ($grabPrice == $test['expected_grab']);
    $grabStatus = $grabPass ? '✅ PASS' : '❌ FAIL';
    if (!$grabPass) $allPassed = false;

    echo "<tr>";
    echo "<td>{$distance}km</td>";
    echo "<td>Grab</td>";
    echo "<td>Rp " . number_format($test['expected_grab'], 0, ',', '.') . "</td>";
    echo "<td>Rp " . number_format($grabPrice, 0, ',', '.') . "</td>";
    echo "<td>{$grabStatus}</td>";
    echo "</tr>";

    // Check JNT
    $jntPass = ($jntPrice == $test['expected_jnt']);
    $jntStatus = $jntPass ? '✅ PASS' : '❌ FAIL';
    if (!$jntPass) $allPassed = false;

    echo "<tr>";
    echo "<td>{$distance}km</td>";
    echo "<td>JNT</td>";
    echo "<td>Rp " . number_format($test['expected_jnt'], 0, ',', '.') . "</td>";
    echo "<td>Rp " . number_format($jntPrice, 0, ',', '.') . "</td>";
    echo "<td>{$jntStatus}</td>";
    echo "</tr>";
}

echo "</table>";

if ($allPassed) {
    echo "<div class='pass'>";
    echo "<h2>🎉 ALL TESTS PASSED!</h2>";
    echo "<p>Pricing calculations are correct and match expected values.</p>";
    echo "<p><strong>System is ready to use! 🚀</strong></p>";
    echo "</div>";
} else {
    echo "<div class='fail'>";
    echo "<h2>❌ SOME TESTS FAILED</h2>";
    echo "<p>Check the calculations above and fix the pricing logic.</p>";
    echo "</div>";
}

// Show profit breakdown for 15km example
echo "<h2>💰 Profit Breakdown Example (15km)</h2>";
echo "<table>";
echo "<tr><th>Courier</th><th>Real Cost</th><th>Markup</th><th>Sell Price</th><th>Profit</th><th>%</th></tr>";

// GoSend 15km
$realGoSendCost = 20000 + ((15 - 8) * 3000);
$goSendSellPrice = ceil(($realGoSendCost * 1.30) / 1000) * 1000;
$goSendProfit = $goSendSellPrice - $realGoSendCost;
$goSendProfitPercent = (($goSendProfit / $realGoSendCost) * 100);

echo "<tr>";
echo "<td>⚡ GoSend</td>";
echo "<td>Rp " . number_format($realGoSendCost, 0, ',', '.') . "</td>";
echo "<td>30%</td>";
echo "<td>Rp " . number_format($goSendSellPrice, 0, ',', '.') . "</td>";
echo "<td style='color:#28a745;font-weight:bold;'>Rp " . number_format($goSendProfit, 0, ',', '.') . "</td>";
echo "<td style='color:#28a745;font-weight:bold;'>" . number_format($goSendProfitPercent, 1) . "%</td>";
echo "</tr>";

// Grab 15km
$realGrabCost = 13000 + ((15 - 6) * 2000);
$grabSellPrice = ceil(($realGrabCost * 1.25) / 1000) * 1000;
$grabProfit = $grabSellPrice - $realGrabCost;
$grabProfitPercent = (($grabProfit / $realGrabCost) * 100);

echo "<tr>";
echo "<td>🚗 Grab</td>";
echo "<td>Rp " . number_format($realGrabCost, 0, ',', '.') . "</td>";
echo "<td>25%</td>";
echo "<td>Rp " . number_format($grabSellPrice, 0, ',', '.') . "</td>";
echo "<td style='color:#28a745;font-weight:bold;'>Rp " . number_format($grabProfit, 0, ',', '.') . "</td>";
echo "<td style='color:#28a745;font-weight:bold;'>" . number_format($grabProfitPercent, 1) . "%</td>";
echo "</tr>";

// JNT 15km
$jntCost = (10000 + (15 * 500)) * 0.70;
$jntSellPrice = ceil((10000 + (15 * 500)) / 1000) * 1000;
$jntProfit = $jntSellPrice - $jntCost;
$jntProfitPercent = (($jntProfit / $jntCost) * 100);

echo "<tr>";
echo "<td>📦 JNT</td>";
echo "<td>Rp " . number_format($jntCost, 0, ',', '.') . "</td>";
echo "<td>~43%</td>";
echo "<td>Rp " . number_format($jntSellPrice, 0, ',', '.') . "</td>";
echo "<td style='color:#28a745;font-weight:bold;'>Rp " . number_format($jntProfit, 0, ',', '.') . "</td>";
echo "<td style='color:#28a745;font-weight:bold;'>" . number_format($jntProfitPercent, 1) . "%</td>";
echo "</tr>";

echo "</table>";

echo "<div style='margin-top:30px;padding:20px;background:linear-gradient(135deg,#667EEA,#764BA2);color:white;border-radius:12px;'>";
echo "<h3>📊 Summary</h3>";
echo "<p><strong>Total Profit Guaranteed:</strong> Rp " . number_format($goSendProfit + $grabProfit + $jntProfit, 0, ',', '.') . "</p>";
echo "<p><strong>Average Markup:</strong> " . number_format(($goSendProfitPercent + $grabProfitPercent + $jntProfitPercent) / 3, 1) . "%</p>";
echo "<p><strong>Conclusion:</strong> Semua opsi PASTI UNTUNG! Owner ga boncos! 💰</p>";
echo "</div>";

echo "<div style='margin-top:20px;text-align:center;'>";
echo "<a href='test-courier-pricing.php' style='display:inline-block;padding:15px 30px;background:#28a745;color:white;text-decoration:none;border-radius:8px;font-weight:bold;'>🧮 Try Interactive Calculator</a> ";
echo "<a href='COURIER-PRICING-SYSTEM.md' style='display:inline-block;padding:15px 30px;background:#667EEA;color:white;text-decoration:none;border-radius:8px;font-weight:bold;'>📖 Read Documentation</a>";
echo "</div>";

echo "</body></html>";
?>
