<?php
/**
 * COURIER PRICING TEST & PROFIT CALCULATOR
 * Test GoSend & Grab Express pricing at different distances
 * Show profit margins to ensure store doesn't lose money
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧮 Courier Pricing Calculator - Profit Test</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667EEA 0%, #764BA2 100%);
            padding: 40px 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 24px;
            padding: 48px;
            box-shadow: 0 24px 72px rgba(0, 0, 0, 0.3);
        }
        h1 {
            font-size: 42px;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 12px;
            background: linear-gradient(135deg, #667EEA 0%, #764BA2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .subtitle {
            font-size: 18px;
            color: #6B7280;
            margin-bottom: 48px;
        }
        .calculator {
            background: linear-gradient(135deg, #F9FAFB 0%, #F3F4F6 100%);
            padding: 32px;
            border-radius: 16px;
            margin-bottom: 40px;
            border: 2px solid #E5E7EB;
        }
        .input-group {
            margin-bottom: 24px;
        }
        .input-group label {
            display: block;
            font-size: 16px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 12px;
        }
        .input-group input[type="number"] {
            width: 100%;
            max-width: 400px;
            padding: 16px 20px;
            border: 2px solid #E5E7EB;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .input-group input[type="number"]:focus {
            outline: none;
            border-color: #667EEA;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        .btn-calculate {
            padding: 16px 48px;
            background: linear-gradient(135deg, #667EEA 0%, #764BA2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
        }
        .btn-calculate:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(102, 126, 234, 0.4);
        }
        .results {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
            margin-top: 40px;
        }
        .courier-card {
            background: white;
            border-radius: 16px;
            padding: 28px;
            border: 2px solid #E5E7EB;
            transition: all 0.3s;
        }
        .courier-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.1);
        }
        .courier-card.gosend {
            border-color: #10B981;
            background: linear-gradient(135deg, #ECFDF5 0%, #D1FAE5 100%);
        }
        .courier-card.grab {
            border-color: #667EEA;
            background: linear-gradient(135deg, #EEF2FF 0%, #E0E7FF 100%);
        }
        .courier-card.jnt {
            border-color: #F59E0B;
            background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%);
        }
        .courier-name {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .badge.fastest { background: #10B981; color: white; }
        .badge.cheap { background: #667EEA; color: white; }
        .badge.economy { background: #F59E0B; color: white; }
        .price-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            font-size: 15px;
        }
        .price-row.total {
            border-bottom: none;
            border-top: 2px solid rgba(0, 0, 0, 0.2);
            margin-top: 12px;
            padding-top: 16px;
            font-size: 18px;
            font-weight: 700;
        }
        .price-row .label {
            color: #6B7280;
        }
        .price-row .value {
            font-weight: 600;
            color: #1F2937;
        }
        .profit {
            background: #10B981;
            color: white;
            padding: 16px;
            border-radius: 12px;
            margin-top: 16px;
            text-align: center;
            font-size: 20px;
            font-weight: 700;
        }
        .info-box {
            background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%);
            border: 2px solid #F59E0B;
            border-radius: 16px;
            padding: 24px;
            margin-top: 40px;
        }
        .info-box h3 {
            font-size: 24px;
            color: #92400E;
            margin-bottom: 16px;
        }
        .info-box ul {
            list-style: none;
            padding: 0;
        }
        .info-box li {
            padding: 8px 0;
            color: #92400E;
            font-size: 15px;
        }
        .info-box li strong {
            color: #78350F;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧮 Courier Pricing Calculator</h1>
        <p class="subtitle">Hitung profit dari GoSend, Grab, dan JNT berdasarkan jarak pengiriman</p>

        <div class="calculator">
            <div class="input-group">
                <label for="distance">📍 Jarak Pengiriman (KM):</label>
                <input type="number" id="distance" value="15" min="1" max="30" step="0.5">
            </div>
            <button class="btn-calculate" onclick="calculate()">🔥 Hitung Pricing & Profit</button>
        </div>

        <div class="results" id="results"></div>

        <div class="info-box">
            <h3>💡 Cara Kerja Pricing System:</h3>
            <ul>
                <li>✅ <strong>GoSend:</strong> Base Rp 20,000 (8km) + Rp 3,000/km • Markup: 30% • Paling cepat (1-2 jam)</li>
                <li>✅ <strong>Grab:</strong> Base Rp 13,000 (6km) + Rp 2,000/km • Markup: 25% • Same day (3-6 jam)</li>
                <li>✅ <strong>JNT:</strong> Base Rp 10,000 + Rp 500/km • Ekonomis • Regular (1-2 hari)</li>
                <li>🔒 <strong>Semua harga sudah dibulatkan</strong> ke ribuan terdekat untuk tampilan lebih rapi</li>
                <li>💰 <strong>Profit dijamin aman</strong> karena sudah include markup di atas real cost</li>
            </ul>
        </div>
    </div>

    <script>
        function calculate() {
            const distance = parseFloat(document.getElementById('distance').value);

            if (!distance || distance <= 0) {
                alert('Masukkan jarak yang valid!');
                return;
            }

            const results = [];

            // GOSEND EXPRESS
            let realGoSendCost = 20000;
            if (distance > 8) {
                realGoSendCost += (distance - 8) * 3000;
            }
            let goSendPrice = realGoSendCost * 1.30;
            goSendPrice = Math.ceil(goSendPrice / 1000) * 1000;
            let goSendProfit = goSendPrice - realGoSendCost;
            let goSendProfitPercent = ((goSendProfit / realGoSendCost) * 100).toFixed(1);

            results.push({
                name: '⚡ GoSend Express',
                badge: 'TERCEPAT',
                badgeClass: 'fastest',
                cardClass: 'gosend',
                realCost: realGoSendCost,
                markup: 30,
                price: goSendPrice,
                profit: goSendProfit,
                profitPercent: goSendProfitPercent,
                duration: '1-2 Jam'
            });

            // GRAB EXPRESS
            let realGrabCost = 13000;
            if (distance > 6) {
                realGrabCost += (distance - 6) * 2000;
            }
            let grabPrice = realGrabCost * 1.25;
            grabPrice = Math.ceil(grabPrice / 1000) * 1000;
            let grabProfit = grabPrice - realGrabCost;
            let grabProfitPercent = ((grabProfit / realGrabCost) * 100).toFixed(1);

            results.push({
                name: '🚗 Grab Express',
                badge: 'HEMAT',
                badgeClass: 'cheap',
                cardClass: 'grab',
                realCost: realGrabCost,
                markup: 25,
                price: grabPrice,
                profit: grabProfit,
                profitPercent: grabProfitPercent,
                duration: '3-6 Jam'
            });

            // JNT REGULAR
            let jntPrice = 10000 + (distance * 500);
            jntPrice = Math.ceil(jntPrice / 1000) * 1000;
            let jntRealCost = jntPrice * 0.7;
            let jntProfit = jntPrice - jntRealCost;
            let jntProfitPercent = ((jntProfit / jntRealCost) * 100).toFixed(1);

            results.push({
                name: '📦 JNT Regular',
                badge: 'EKONOMIS',
                badgeClass: 'economy',
                cardClass: 'jnt',
                realCost: jntRealCost,
                markup: 43,
                price: jntPrice,
                profit: jntProfit,
                profitPercent: jntProfitPercent,
                duration: '1-2 Hari'
            });

            renderResults(results, distance);
        }

        function renderResults(results, distance) {
            const container = document.getElementById('results');
            container.innerHTML = results.map(r => `
                <div class="courier-card ${r.cardClass}">
                    <div class="courier-name">
                        ${r.name}
                        <span class="badge ${r.badgeClass}">${r.badge}</span>
                    </div>

                    <div class="price-row">
                        <span class="label">📍 Jarak</span>
                        <span class="value">${distance}km</span>
                    </div>

                    <div class="price-row">
                        <span class="label">💸 Real Cost (Kurir)</span>
                        <span class="value">Rp ${formatNumber(r.realCost)}</span>
                    </div>

                    <div class="price-row">
                        <span class="label">📈 Markup</span>
                        <span class="value">${r.markup}%</span>
                    </div>

                    <div class="price-row total">
                        <span class="label">💰 Harga Jual</span>
                        <span class="value">Rp ${formatNumber(r.price)}</span>
                    </div>

                    <div class="profit">
                        🎉 Profit: Rp ${formatNumber(r.profit)} (${r.profitPercent}%)
                    </div>

                    <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid rgba(0,0,0,0.1); text-align: center; color: #6B7280; font-size: 14px;">
                        ⏱️ Delivery: ${r.duration}
                    </div>
                </div>
            `).join('');
        }

        function formatNumber(num) {
            return new Intl.NumberFormat('id-ID').format(Math.round(num));
        }

        calculate();
    </script>
</body>
</html>
