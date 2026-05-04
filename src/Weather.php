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

        // 今日の概況
        $office = $data[0]['publishingOffice'];
        $todayArea = $data[0]['timeSeries'][0]['areas'][0];
        
        // 3時間ごとの詳細データ（時系列）
        $timeSeries = $data[0]['timeSeries'][0]['timeDefines'];
        $weathers = $data[0]['timeSeries'][0]['areas'][0]['weathers'];

        $hourly = [];
        for ($i = 0; $i < min(5, count($timeSeries)); $i++) {
            $hourly[] = [
                'time' => date('H:i', strtotime($timeSeries[$i])),
                'desc' => $weathers[$i] ?? $todayArea['weathers'][0]
            ];
        }

        return [
            'area' => $office,
            'today' => $todayArea['weathers'][0],
            'hourly' => $hourly
        ];
    }
}