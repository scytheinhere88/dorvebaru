<?php
require_once __DIR__ . '/config.php';

echo "<h1>🕐 TIMEZONE DEBUG</h1>";
echo "<style>body{font-family:monospace;padding:20px;} table{border-collapse:collapse;margin:20px 0;} th,td{border:1px solid #ddd;padding:12px;} th{background:#f0f0f0;} .error{color:red;font-weight:bold;} .success{color:green;font-weight:bold;}</style>";

echo "<h2>PHP Timezone vs MySQL Timezone</h2>";

echo "<table>";
echo "<tr><th>Source</th><th>Current Time</th><th>Timezone</th></tr>";

// PHP time
echo "<tr>";
echo "<td><strong>PHP</strong></td>";
echo "<td>" . date('Y-m-d H:i:s') . "</td>";
echo "<td>" . date_default_timezone_get() . "</td>";
echo "</tr>";

// MySQL NOW()
$stmt = $pdo->query("SELECT NOW() as mysql_now, @@session.time_zone as tz");
$result = $stmt->fetch();

echo "<tr>";
echo "<td><strong>MySQL NOW()</strong></td>";
echo "<td>" . $result['mysql_now'] . "</td>";
echo "<td>" . $result['tz'] . "</td>";
echo "</tr>";

echo "</table>";

$phpTime = strtotime(date('Y-m-d H:i:s'));
$mysqlTime = strtotime($result['mysql_now']);
$diff = $phpTime - $mysqlTime;

if ($diff != 0) {
    echo "<p class='error'>❌ TIMEZONE MISMATCH! Difference: " . abs($diff) . " seconds (" . round(abs($diff)/3600, 1) . " hours)</p>";
} else {
    echo "<p class='success'>✅ Timezones match!</p>";
}

echo "<hr>";

// Check the actual voucher data
echo "<h2>Voucher Time Check</h2>";

$stmt = $pdo->query("
    SELECT
        id,
        code,
        valid_from,
        valid_until,
        NOW() as mysql_now,
        (valid_from <= NOW()) as has_started,
        (valid_until >= NOW()) as not_expired,
        is_active
    FROM vouchers
");
$vouchers = $stmt->fetchAll();

echo "<table>";
echo "<tr>";
echo "<th>Code</th>";
echo "<th>valid_from</th>";
echo "<th>valid_until</th>";
echo "<th>MySQL NOW()</th>";
echo "<th>Started?</th>";
echo "<th>Expired?</th>";
echo "<th>Active?</th>";
echo "<th>Should Show?</th>";
echo "</tr>";

foreach ($vouchers as $v) {
    $shouldShow = $v['has_started'] && $v['not_expired'] && $v['is_active'];
    $bgColor = $shouldShow ? '#D1FAE5' : '#FEE2E2';

    echo "<tr style='background:{$bgColor};'>";
    echo "<td><strong>" . htmlspecialchars($v['code']) . "</strong></td>";
    echo "<td>{$v['valid_from']}</td>";
    echo "<td>{$v['valid_until']}</td>";
    echo "<td>{$v['mysql_now']}</td>";
    echo "<td>" . ($v['has_started'] ? '✅' : '❌') . "</td>";
    echo "<td>" . ($v['not_expired'] ? '✅' : '❌') . "</td>";
    echo "<td>" . ($v['is_active'] ? '✅' : '❌') . "</td>";
    echo "<td><strong>" . ($shouldShow ? '✅ YES' : '❌ NO') . "</strong></td>";
    echo "</tr>";
}

echo "</table>";

// Show actual query test
echo "<hr>";
echo "<h2>Test Query with User ID 3</h2>";

$stmt = $pdo->prepare("
    SELECT
        v.id,
        v.code,
        v.valid_from,
        v.valid_until,
        NOW() as now,
        v.is_active,
        (v.valid_from <= NOW()) as started,
        (v.valid_until >= NOW()) as not_expired
    FROM user_vouchers uv
    INNER JOIN vouchers v ON uv.voucher_id = v.id
    WHERE uv.user_id = 3
");
$stmt->execute();
$results = $stmt->fetchAll();

echo "<p>Found: <strong>" . count($results) . "</strong> rows (before filtering)</p>";

echo "<table>";
echo "<tr><th>Code</th><th>valid_from</th><th>NOW()</th><th>valid_until</th><th>Started</th><th>Not Expired</th><th>Active</th><th>Pass Filter?</th></tr>";

foreach ($results as $r) {
    $passFilter = $r['started'] && $r['not_expired'] && $r['is_active'];
    $bgColor = $passFilter ? '#D1FAE5' : '#FEE2E2';

    echo "<tr style='background:{$bgColor};'>";
    echo "<td><strong>" . htmlspecialchars($r['code']) . "</strong></td>";
    echo "<td>{$r['valid_from']}</td>";
    echo "<td>{$r['now']}</td>";
    echo "<td>{$r['valid_until']}</td>";
    echo "<td>" . ($r['started'] ? '✅ YES' : '❌ NO') . "</td>";
    echo "<td>" . ($r['not_expired'] ? '✅ YES' : '❌ NO') . "</td>";
    echo "<td>" . ($r['is_active'] ? '✅ YES' : '❌ NO') . "</td>";
    echo "<td><strong>" . ($passFilter ? '✅ PASS' : '❌ FAIL') . "</strong></td>";
    echo "</tr>";
}

echo "</table>";

// Final query with WHERE clause
echo "<hr>";
echo "<h2>FINAL: Query WITH WHERE filters</h2>";

$stmt = $pdo->prepare("
    SELECT v.*
    FROM user_vouchers uv
    INNER JOIN vouchers v ON uv.voucher_id = v.id
    WHERE uv.user_id = 3
      AND v.is_active = 1
      AND v.valid_from <= NOW()
      AND v.valid_until >= NOW()
");
$stmt->execute();
$final = $stmt->fetchAll();

echo "<p class='" . (count($final) > 0 ? 'success' : 'error') . "'>Result: <strong>" . count($final) . "</strong> vouchers</p>";

if (count($final) > 0) {
    foreach ($final as $f) {
        echo "<p>✅ " . htmlspecialchars($f['code']) . "</p>";
    }
} else {
    echo "<p class='error'>❌ NO RESULTS! This is why member page is empty.</p>";
}
?>
