<?php
require_once __DIR__ . '/vendor/autoload.php';
use App\Weather;

date_default_timezone_set('Asia/Tokyo');
$selectedArea = $_GET['area'] ?? ''; // 初期値は空にしてJSに任せる
$areas = [
    '130000' => '東京',
    '140000' => '横浜',
    '080010' => '茨城',
    '270000' => '大阪',
    '290000' => '奈良',
    '460100' => '鹿児島',
    '400000' => '福岡',
    '016000' => '札幌'
];

// 表示エリアを確定
$displayArea = $selectedArea ?: '270000'; 

$weather = new Weather();
$data = null;

try {
    $data = $weather->getForecast($displayArea);
} catch (\Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="manifest" href="manifest.json">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Add9th Weather App</title>
    <style>
        :root { --main: #0f172a; --accent: #3b82f6; --success: #10b981; }
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; color: var(--main); margin: 0; padding: 20px; }
        .app-container { max-width: 500px; margin: 0 auto; }
        .card { background: white; border-radius: 24px; padding: 24px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); margin-bottom: 20px; }
        select { width: 100%; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 10px; font-size: 16px; background: #fff; }
        button { width: 100%; padding: 12px; border: none; border-radius: 12px; font-weight: bold; cursor: pointer; color: white; transition: opacity 0.2s; }
        button:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-update { background: var(--accent); }
        .btn-geo { background: var(--success); margin-top: 10px; width: 100%; }
        .hourly-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(70px, 1fr)); gap: 10px; margin-top: 20px; }
        .hourly-item { background: #eff6ff; padding: 10px; border-radius: 12px; text-align: center; font-size: 12px; }
        .weather-main { font-size: 48px; margin: 10px 0; }
        .chart-container { position: relative; height: 180px; width: 100%; }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- 設定カード -->
        <div class="card">
            <form id="weather-form" method="GET">
                <select name="area" id="area-select" onchange="saveArea(this.value)">
                    <?php foreach ($areas as $code => $name): ?>
                        <option value="<?= $code ?>" <?= $displayArea === $code ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-update">予報を更新</button>
                <button type="button" id="geo-btn" onclick="getLocation()" class="btn-geo">📍 現在地から取得</button>
            </form>
        </div>

        <?php if ($data): ?>
            <!-- 天気概況 -->
            <div class="card" style="text-align: center;">
                <div style="color: #64748b;"><?= htmlspecialchars($areas[$displayArea]) ?> の概況</div>
                <div class="weather-main">
                    <?= str_contains($data['today'], '晴') ? '☀️' : (str_contains($data['today'], '雨') ? '☔️' : '☁️') ?>
                </div>
                <div style="font-weight: bold; font-size: 1.1rem;"><?= htmlspecialchars($data['today']) ?></div>
                
                <div class="hourly-grid">
                    <?php foreach ($data['hourly'] as $h): ?>
                        <div class="hourly-item">
                            <div style="color: #64748b;"><?= $h['time'] ?></div>
                            <div style="font-size: 1.2rem; margin: 4px 0;">
                                <?= str_contains($h['desc'], '晴') ? '☀️' : (str_contains($h['desc'], '雨') ? '☔️' : '☁️') ?>
                            </div>
                            <div style="color: var(--accent); font-weight: bold;"><?= $h['temp'] ?>℃</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- グラフ表示 -->
            <div class="card">
                <div class="chart-container">
                    <canvas id="tempChart"></canvas>
                </div>
            </div>
        <?php endif; ?>
    </div>

<script>
// 1. PWA & キャッシュ初期化
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('sw.js');
}

// 2. 記憶機能（LocalStorage）
window.onload = () => {
    const savedArea = localStorage.getItem('selectedArea');
    const currentParam = new URLSearchParams(window.location.search).get('area');
    if (!currentParam && savedArea) {
        window.location.search = `?area=${savedArea}`;
    }
};

function saveArea(code) {
    localStorage.setItem('selectedArea', code);
}

function getLocation() {
    const btn = document.getElementById('geo-btn');
    const originalText = "📍 現在地から取得";
    btn.innerHTML = "📍 判定中...";
    btn.disabled = true;

    navigator.geolocation.getCurrentPosition((position) => {
        const lat = position.coords.latitude;
        const lon = position.coords.longitude;
        
        const areaMapping = [
            { code: '130000', lat: 35.68, lon: 139.76, name: '東京' },
            { code: '140000', lat: 35.44, lon: 139.63, name: '横浜' },
            { code: '080010', lat: 36.36, lon: 140.47, name: '茨城' },
            { code: '270000', lat: 34.69, lon: 135.50, name: '大阪' },
            { code: '290000', lat: 34.68, lon: 135.83, name: '奈良' },
            { code: '460100', lat: 31.56, lon: 130.55, name: '鹿児島' },
            { code: '400000', lat: 33.59, lon: 130.40, name: '福岡' }
        ];

        let closest = areaMapping[0];
        let minDist = Infinity;
        areaMapping.forEach(area => {
            const dist = Math.sqrt(Math.pow(lat - area.lat, 2) + Math.pow(lon - area.lon, 2));
            if (dist < minDist) { minDist = dist; closest = area; }
        });

        saveArea(closest.code);
        window.location.search = `?area=${closest.code}`;
    }, (error) => {
        // エラー内容を詳しく表示
        let msg = "";
        switch(error.code) {
            case 1: msg = "位置情報の利用が許可されていません。ブラウザの設定を確認してください。"; break;
            case 2: msg = "デバイスの位置を特定できませんでした。"; break;
            case 3: msg = "タイムアウトしました。電波の良い場所で再度お試しください。"; break;
            default: msg = "不明なエラーが発生しました。"; break;
        }
        alert("位置情報エラー: " + msg);
        
        btn.innerHTML = originalText;
        btn.disabled = false;
    }, {
        enableHighAccuracy: true, // 確実に取るために一度trueにしてみます
        timeout: 10000,           // 10秒に延長
        maximumAge: 0             // キャッシュを使わず最新の場所を探す
    });
}
// 4. グラフ描画（Chart.js）
<?php if ($data): ?>
    const ctx = document.getElementById('tempChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($data['chartLabels']) ?>,
            datasets: [{
                label: '気温',
                data: <?= json_encode($data['chartData']) ?>,
                borderColor: '#3b82f6',
                tension: 0.4,
                fill: true,
                backgroundColor: 'rgba(59, 130, 246, 0.1)'
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { ticks: { callback: v => v + '℃' } } }
        }
    });
<?php endif; ?>
</script>
</body>
</html>