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

        // 基本情報の抽出
        $office = $data[0]['publishingOffice'];
        $todayData = $data[0]['timeSeries'][0];
        
        // 3時間ごと（時系列）のデータを取得
        // 気象庁のデータ構造に合わせて、時系列配列(timeSeries[1]など)から取得するよう調整
        $timeDefines = $data[0]['timeSeries'][0]['timeDefines'];
        $weathers = $data[0]['timeSeries'][0]['areas'][0]['weathers'];

        $hourly = [];
        // 最大5件分、時間をわかりやすくフォーマットして抽出
        for ($i = 0; $i < min(5, count($timeDefines)); $i++) {
            $timeLabel = date('m/d H:i', strtotime($timeDefines[$i]));
            // もし今日なら「今日 12:00」のように表示
            if (date('Y-m-d') === date('Y-m-d', strtotime($timeDefines[$i]))) {
                $timeLabel = "今日 " . date('H:i', strtotime($timeDefines[$i]));
            }

            $hourly[] = [
                'time' => $timeLabel,
                'desc' => $weathers[$i]
            ];
        }

        return [
            'area' => $office,
            'today' => $weathers[0],
            'hourly' => $hourly
        ];
    }
}