<?php

namespace app\services;

use Yii;

class YandexTaxi extends Executer
{
    private $parkID;

    public function __construct($clientID, $apiKey, $parkID)
    {
        $this->addHeader("Content-Type: application/json; charset=utf-8");
        $this->addHeader("X-Client-ID: {$clientID}");
        $this->addHeader("X-Api-Key: {$apiKey}");
        $this->addHeader("Accept-Language: ru");
        $this->parkID = $parkID;
    }

    public function driverProfiles($phone)
    {
        $this->addArrayParams([
            'query' => [
                'text' => $phone,
                'park' => [
                    'id' => $this->parkID,
                ],
            ]
        ]);

        return json_decode($this->post('https://fleet-api.taxi.yandex.net/v1/parks/driver-profiles/list', true));
    }

    public function pay($driver_profile_id, $amount)
    {
        $token = uniqid('token_', true);
        $this->addHeader("X-Idempotency-Token: {$token}");
        $this->addArrayParams([
            'amount' => (string) $amount,
            'category_id' => 'partner_service_manual',
            'description' => 'test',
            'driver_profile_id' => $driver_profile_id,
            'park_id' => $this->parkID,
        ]);

        Yii::info("Транзакция YandexTaxi::pay на сумму {$amount}, водитель {$driver_profile_id}", 'API');

        Yii::info("Транзакция YandexTaxi::pay::params" . print_r($this->params, true), 'API');

        $result = json_decode($this->post('https://fleet-api.taxi.yandex.net/v2/parks/driver-profiles/transactions', true));

        Yii::info("Транзакция YandexTaxi::pay::result" . print_r($result, true), 'API');

        return $result;
    }
}