<?php
namespace App;

use GuzzleHttp\Client;

class Weather {
    private $client;

    public function __construct() {
        // Guzzleのクライアント（通信するための道具）を準備
        $this->client = new Client([
            'base_uri' => 'https://www.jma.go.jp/bosai/forecast/data/forecast/',
            'timeout'  => 5.0,
        ]);
    }

    public function getForecast($areaCode) {
        // 気象庁のAPIを叩く（例：東京は 130000）
        $response = $this->client->request('GET', "{$areaCode}.json");
        
        // 届いたデータ（JSON形式）をPHPの配列に変換
        $data = json_decode($response->getBody()->getContents(), true);

        // 必要な情報（エリア名と天気）だけを抜き出して返す
        return [
            'area' => $data[0]['publishingOffice'],
            'forecast' => $data[0]['timeSeries'][0]['areas'][0]['weathers'][0]
        ];
    }
}