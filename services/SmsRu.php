<?php

namespace app\services;

use Yii;

class SmsRu extends Executer
{
    
    public function send($phone, $text)
    {
        if (!is_string($text)) {
            throw new \Exception('Сообщение не является строкой');
        }

        $this->addArrayParams([
            'api_id' => Yii::$app->params['sms_ru_api_id'],
            'to'     => $phone,
            'msg'    => $text,
            'json'   => 1,
            // 'from'   => Yii::$app->params['sms_ru_from'],
        ]);

        $result = json_decode($this->get('https://sms.ru/sms/send'));

        Yii::info("Отправка sms на номер {$phone} с текстом {$text}", 'API');

        if ($result && $result->status == 'OK') {
            Yii::info("Отправка sms на номер {$phone} успешна", 'API');
            return true;
        }

        Yii::error("Ошибка отправки sms на номер {$phone}" . print_r($result, true), 'API');

        return false;
    }
}