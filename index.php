<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Weather;

date_default_timezone_set('Asia/Tokyo');

$weather = new Weather();

try {
    // 大阪（270000）の天気を取得
    $info = $weather->getForecast('270000');

    // ブラウザ向けにHTMLで出力
    echo "<h1>--- お天気情報 ---</h1>";
    echo "<p>発表元: " . $info['area'] . "</p>";
    echo "<p>今日の天気: <strong>" . $info['forecast'] . "</strong></p>";
    echo "<p>取得時刻: " . date('H:i:s') . "</p>";

    echo "<hr>"; // 横線

    if (str_contains($info['forecast'], '晴')) {
        echo "<h2 style='color: orange;'>☀️ 絶好のバンド日和ですね！</h2>";
    } elseif (str_contains($info['forecast'], '雨')) {
        echo "<h2 style='color: blue;'>☔️ 今日はじっくりコードを書きましょう。</h2>";
    } else {
        echo "<h2>☁️ 落ち着いた天気です。作業に集中できそうですね。</h2>";
    }

} catch (\Exception $e) {
    echo "<h1>エラーが発生しました</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}