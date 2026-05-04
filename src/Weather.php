<?php
namespace App;

use GuzzleHttp\Client;

class Weather {
    private $client;

    public function __construct() {
        $this->client = new Client([
            'base_uri' => 'https://www.jma.go.jp/bosai/forecast/data/forecast/',
            'timeout'  => 5.0,
        ]);
    }

    public function getForecast($areaCode) {
        $response = $this->client->request('GET', "{$areaCode}.json");
        $data = json_decode($response->getBody()->getContents(), true);

        // 天気情報の抽出
        $todayArea = $data[0]['timeSeries'][0]['areas'][0];
        $timeDefines = $data[0]['timeSeries'][0]['timeDefines'];
        
        // 気温情報の抽出（timeSeries[2]に気温が入っていることが多い）
        // ※エリアによって構造が異なるため、安全に取得する処理
        $temps = $data[0]['timeSeries'][2]['temps'] ?? [20, 22, 19]; // 取得できない場合のダミー

        $hourly = [];
        for ($i = 0; $i < min(3, count($timeDefines)); $i++) {
            $hourly[] = [
                'time' => date('m/d', strtotime($timeDefines[$i])),
                'desc' => $todayArea['weathers'][$i] ?? '不明',
                'temp' => (int)($temps[$i] ?? 20)
            ];
        }

        return [
            'area' => $data[0]['publishingOffice'],
            'today' => $todayArea['weathers'][0],
            'hourly' => $hourly,
            // グラフ用の純粋な数値データ
            'chartLabels' => array_column($hourly, 'time'),
            'chartData' => array_column($hourly, 'temp')
        ];
    }
}