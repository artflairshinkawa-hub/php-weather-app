<?php
require_once __DIR__ . '/vendor/autoload.php';
use App\Weather;

date_default_timezone_set('Asia/Tokyo');
$selectedArea = $_GET['area'] ?? '270000';
$areas = ['130000' => '東京', '140000' => '横浜', '270000' => '大阪', '400000' => '福岡'];

$weather = new Weather();
$data = null;

try {
    $data = $weather->getForecast($selectedArea);
} catch (\Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Advanced Weather App</title>
    <style>
        :root { --main: #0f172a; --accent: #3b82f6; }
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; color: var(--main); margin: 0; padding: 20px; }
        .app-container { max-width: 500px; margin: 0 auto; }
        .card { background: white; border-radius: 24px; padding: 24px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); margin-bottom: 20px; }
        select { width: 100%; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 10px; font-size: 16px; background: #fff; }
        button { width: 100%; padding: 12px; background: var(--accent); color: white; border: none; border-radius: 12px; font-weight: bold; cursor: pointer; }
        .hourly-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(70px, 1fr)); gap: 10px; margin-top: 20px; }
        .hourly-item { background: #eff6ff; padding: 10px; border-radius: 12px; text-align: center; font-size: 12px; }
        .weather-main { font-size: 48px; margin: 10px 0; }
        .chart-container { position: relative; height: 200px; width: 100%; }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- 地域選択カード -->
<div class="card">
    <form id="weather-form" method="GET">
        <select name="area" id="area-select">
            <?php foreach ($areas as $code => $name): ?>
                <option value="<?= $code ?>" <?= $selectedArea === $code ? 'selected' : '' ?>><?= $name ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">予報を更新</button>
        <!-- 現在地取得ボタンを追加 -->
        <button type="button" onclick="getLocation()" style="background: #10b981; margin-top: 10px;">📍 現在地から取得</button>
    </form>
</div>

<script>
function getLocation() {
    if (!navigator.geolocation) {
        alert("お使いのブラウザは位置情報に対応していません。");
        return;
    }

    navigator.geolocation.getCurrentPosition((position) => {
        const lat = position.coords.latitude;
        const lon = position.coords.longitude;
        
        // 本来はここで緯度経度からエリアコードを逆引きしますが、
        // 今回は「近接判定」のデモとして、大阪・東京・横浜・福岡のどこに近いか判定します
        const areaMapping = [
            { code: '130000', lat: 35.68, lon: 139.76, name: '東京' },
            { code: '140000', lat: 35.44, lon: 139.63, name: '横浜' },
            { code: '270000', lat: 34.69, lon: 135.50, name: '大阪' },
            { code: '400000', lat: 33.59, lon: 130.40, name: '福岡' }
        ];

        // 最も近い距離のエリアを選択
        let closest = areaMapping[0];
        let minDist = Infinity;

        areaMapping.forEach(area => {
            const dist = Math.sqrt(Math.pow(lat - area.lat, 2) + Math.pow(lon - area.lon, 2));
            if (dist < minDist) {
                minDist = dist;
                closest = area;
            }
        });

        alert(`${closest.name}付近にいることを確認しました！`);
        document.getElementById('area-select').value = closest.code;
        document.getElementById('weather-form').submit();
    }, () => {
        alert("位置情報の取得に失敗しました。");
    });
}
</script>

        <?php if ($data): ?>
            <!-- メイン予報カード -->
            <div class="card" style="text-align: center;">
                <div style="color: #64748b;"><?= htmlspecialchars($areas[$selectedArea]) ?> の現在の概況</div>
                <div class="weather-main">
                    <?= str_contains($data['today'], '晴') ? '☀️' : (str_contains($data['today'], '雨') ? '☔️' : '☁️') ?>
                </div>
                <div style="font-weight: bold; font-size: 18px;"><?= htmlspecialchars($data['today']) ?></div>
                
                <div class="hourly-grid">
                    <?php foreach ($data['hourly'] as $h): ?>
                        <div class="hourly-item">
                            <div style="color: #64748b; margin-bottom: 5px;"><?= $h['time'] ?></div>
                            <div style="font-size: 18px;">
                                <?= str_contains($h['desc'], '晴') ? '☀️' : (str_contains($h['desc'], '雨') ? '☔️' : '☁️') ?>
                            </div>
                            <div style="color: #3b82f6; font-weight: bold;"><?= $h['temp'] ?>℃</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 気温グラフカード -->
            <div class="card">
                <h3 style="margin-top: 0; font-size: 1rem; color: #64748b;">気温の推移</h3>
                <div class="chart-container">
                    <canvas id="tempChart"></canvas>
                </div>
            </div>

            <script>
                const ctx = document.getElementById('tempChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: <?= json_encode($data['chartLabels']) ?>,
                        datasets: [{
                            label: '予想気温 (℃)',
                            data: <?= json_encode($data['chartData']) ?>,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4,
                            pointBackgroundColor: '#3b82f6'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { 
                                beginAtZero: false,
                                ticks: { callback: function(value) { return value + '℃'; } }
                            }
                        },
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            </script>
        <?php endif; ?>
    </div>
</body>
</html>