<?php
/**
 * TEST SHIPPING WITH KNOWN WORKING ADDRESSES
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Shipping Addresses</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
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
        .header h1 { font-size: 28px; margin-bottom: 8px; }
        .content { padding: 30px; }
        .address-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            border-left: 4px solid #667eea;
            cursor: pointer;
            transition: all 0.3s;
        }
        .address-card:hover {
            transform: translateX(8px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            border-left-color: #764ba2;
        }
        .address-card h3 {
            color: #667eea;
            font-size: 18px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .address-card p {
            color: #6c757d;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 8px;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            background: #d4edda;
            color: #155724;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 8px;
        }
        .badge.warning {
            background: #fff3cd;
            color: #856404;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            margin-top: 10px;
        }
        .btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }
        .note {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
        }
        .note h3 {
            color: #856404;
            margin-bottom: 12px;
            font-size: 18px;
        }
        .note ul {
            margin-left: 20px;
            color: #856404;
        }
        .note li {
            margin-bottom: 8px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚚 Test Shipping Addresses</h1>
            <p>Alamat-alamat yang dijamin ada courier service</p>
        </div>

        <div class="content">
            <h2 style="margin-bottom: 20px; color: #1F2937;">📍 Working Test Addresses</h2>

            <!-- Jakarta -->
            <div class="address-card" onclick="window.location.href='/debug-shipping.php?lat=-6.2088&lng=106.8456&postal=10110'">
                <h3>🏙️ Jakarta Pusat</h3>
                <p><strong>Alamat:</strong> Jl. MH Thamrin No.1, Menteng, Jakarta Pusat</p>
                <p><strong>Coordinates:</strong> Lat: -6.2088, Lng: 106.8456</p>
                <p><strong>Postal Code:</strong> 10110</p>
                <span class="badge">✅ All Couriers Available</span>
                <a href="/debug-shipping.php?lat=-6.2088&lng=106.8456&postal=10110" class="btn" onclick="event.stopPropagation()">Test Sekarang</a>
            </div>

            <!-- Bandung -->
            <div class="address-card" onclick="window.location.href='/debug-shipping.php?lat=-6.9175&lng=107.6191&postal=40111'">
                <h3>🏔️ Bandung</h3>
                <p><strong>Alamat:</strong> Jl. Asia Afrika No.8, Bandung</p>
                <p><strong>Coordinates:</strong> Lat: -6.9175, Lng: 107.6191</p>
                <p><strong>Postal Code:</strong> 40111</p>
                <span class="badge">✅ All Couriers Available</span>
                <a href="/debug-shipping.php?lat=-6.9175&lng=107.6191&postal=40111" class="btn" onclick="event.stopPropagation()">Test Sekarang</a>
            </div>

            <!-- Surabaya -->
            <div class="address-card" onclick="window.location.href='/debug-shipping.php?lat=-7.2575&lng=112.7521&postal=60119'">
                <h3>🏢 Surabaya</h3>
                <p><strong>Alamat:</strong> Jl. Pemuda No.1, Surabaya</p>
                <p><strong>Coordinates:</strong> Lat: -7.2575, Lng: 112.7521</p>
                <p><strong>Postal Code:</strong> 60119</p>
                <span class="badge">✅ All Couriers Available</span>
                <a href="/debug-shipping.php?lat=-7.2575&lng=112.7521&postal=60119" class="btn" onclick="event.stopPropagation()">Test Sekarang</a>
            </div>

            <!-- Medan -->
            <div class="address-card" onclick="window.location.href='/debug-shipping.php?lat=-3.5952&lng=98.6722&postal=20111'">
                <h3>🌴 Medan (Lebih Jauh dari Binjai)</h3>
                <p><strong>Alamat:</strong> Jl. Balai Kota No.1, Medan</p>
                <p><strong>Coordinates:</strong> Lat: -3.5952, Lng: 98.6722</p>
                <p><strong>Postal Code:</strong> 20111</p>
                <span class="badge">✅ Most Couriers Available</span>
                <a href="/debug-shipping.php?lat=-3.5952&lng=98.6722&postal=20111" class="btn" onclick="event.stopPropagation()">Test Sekarang</a>
            </div>

            <!-- Yogyakarta -->
            <div class="address-card" onclick="window.location.href='/debug-shipping.php?lat=-7.7956&lng=110.3695&postal=55511'">
                <h3>🏛️ Yogyakarta</h3>
                <p><strong>Alamat:</strong> Jl. Malioboro No.56, Yogyakarta</p>
                <p><strong>Coordinates:</strong> Lat: -7.7956, Lng: 110.3695</p>
                <p><strong>Postal Code:</strong> 55511</p>
                <span class="badge">✅ All Couriers Available</span>
                <a href="/debug-shipping.php?lat=-7.7956&lng=110.3695&postal=55511" class="btn" onclick="event.stopPropagation()">Test Sekarang</a>
            </div>

            <!-- Binjai (Current - akan fail) -->
            <div class="address-card" onclick="window.location.href='/debug-shipping.php?lat=-3.5952&lng=98.5006&postal=20239'">
                <h3>⚠️ Binjai (Same City - Original)</h3>
                <p><strong>Alamat:</strong> Jl. Anggur, Bandar Senembah, Binjai</p>
                <p><strong>Coordinates:</strong> Lat: -3.5952, Lng: 98.5006</p>
                <p><strong>Postal Code:</strong> 20239</p>
                <span class="badge warning">❌ Terlalu Dekat - No Service</span>
                <a href="/debug-shipping.php?lat=-3.5952&lng=98.5006&postal=20239" class="btn" onclick="event.stopPropagation()">Test (Akan Fail)</a>
            </div>

            <div class="note">
                <h3>⚠️ Kenapa Binjai ke Binjai Ga Ada Courier?</h3>
                <ul>
                    <li><strong>Jarak terlalu dekat:</strong> Origin (postal 20719) dan Destination (postal 20239) masih dalam kota Binjai yang sama</li>
                    <li><strong>Courier policy:</strong> JNE, J&T, SiCepat, dll biasanya ga service same-city atau jarak < 5km</li>
                    <li><strong>Business logic:</strong> Same-city delivery biasanya pakai GoSend, Grab, atau kurir internal</li>
                    <li><strong>Solution:</strong> Test dengan alamat ke kota lain (Jakarta, Bandung, Surabaya) - dijamin ada courier!</li>
                </ul>
            </div>

            <div style="margin-top: 30px; text-align: center;">
                <a href="/debug-shipping.php" class="btn" style="margin-right: 10px;">Back to Debug Tool</a>
                <a href="/pages/checkout.php" class="btn">Go to Checkout</a>
            </div>
        </div>
    </div>
</body>
</html>
