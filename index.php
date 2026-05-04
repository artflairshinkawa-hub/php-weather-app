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
            <form method="GET">
                <select name="area">
                    <?php foreach ($areas as $code => $name): ?>
                        <option value="<?= $code ?>" <?= $selectedArea === $code ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">予報を更新</button>
            </form>
        </div>

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