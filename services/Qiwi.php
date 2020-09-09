<?php

namespace app\services;

use Yii;
use app\models\WithdrawalLog;
use app\models\WithdrawalMethod;

class Qiwi extends Executer
{

    private $_terminal_id = Yii::$app->params['qiwi_terminal_id'];

    private $_password = Yii::$app->params['qiwi_password'];

    private $income_wire_transfer = 1; // 0 - нал, 1 - безнал

    private $service_id = [
        1 => '34020',
        2 => '99',
    ];

    private $log;
    
    public function balance()
    {
        $response = new \stdClass();
        $response->success = true;
        try {
            $this->addHeader('Content-Type: application/xml; charset=utf-8');

            $this->addStringParams(<<<XML
<?xml version="1.0" encoding="utf-8"?>
<request>
    <request-type>ping</request-type>
    <terminal-id>{$this->_terminal_id}</terminal-id>
    <extra name="password">{$this->_password}</extra>
</request>
XML);
            $result = $this->post('https://api.qiwi.com/xml/topup.jsp');

            if (!$result) {
                throw new \Exception('Ошибка получения данных');
            }

            $xml = simplexml_load_string($result);

            if (!$xml) {
                throw new \Exception('Ошибка преобразования данных');
            }

            if (!isset($xml->{'result-code'})) {
                throw new \Exception('Ошибка получения параметров ответа');
            }

            $resultCode = $xml->{'result-code'}->__toString();

            if ($resultCode !== '0') {
                throw new \Exception($xml->{'result-code'}->attributes()->message->__toString());
            }

            $response->code = $xml->balances->balance->attributes()->code->__toString();
            $response->balance = $xml->balances->balance->__toString();

            Yii::info("Получение баланса QIWI: (Код: {$response->code}; Баланс: {$response->balance})", 'API');

        } catch (\Exception $e) {
            $response = new \stdClass();
            $response->success = false;
            $response->err_code = $e->getCode();
            $response->err_line = $e->getLine();
            $response->err_description = $e->getMessage();

            Yii::error("Ошибка получения баланса QIWI: " . print_r($response, true), 'API');
        }

        return $response;
    }

    public function pay($transactionID, $type, $receiver, $amount)
    {
        $response = new \stdClass();
        $response->success = true;
        try {
            $receiver = preg_replace('/[^0-9]/', '', $receiver);

            if ($type == 2) {
                $receiver = '7' . mb_substr($receiver, -10);
            }

            // Комиссия 40 рублей
            $amount -= 40;

            $this->logStart($transactionID, $type, $receiver, $amount);

            $this->addHeader('Content-Type: application/xml; charset=utf-8');

            $this->addStringParams(<<<XML
<?xml version="1.0" encoding="utf-8"?>
<request>
    <request-type>pay</request-type>
    <terminal-id>{$this->_terminal_id}</terminal-id>
    <extra name="password">{$this->_password}</extra>
    <extra name="income_wire_transfer">{$this->income_wire_transfer}</extra>
    <auth>
        <payment>
            <transaction-number>{$transactionID}</transaction-number>
            <from>
                <ccy>RUB</ccy>
            </from>
            <to>
                <amount>{$amount}</amount>
                <ccy>RUB</ccy>
                <service-id>{$this->service_id[$type]}</service-id>
                <account-number>{$receiver}</account-number>
            </to>
        </payment>
    </auth>
</request>
XML);
            $this->logParams($this->params);

            $result = $this->post('https://api.qiwi.com/xml/topup.jsp');

            /*$result = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<response>
<payment status='60' txn_id='6060' transaction-number='12345678' result-code='0' final-status='true' fatal-error='false' txn-date='02.03.2011 14:35:46'  >
  <from>
    <amount>15.00</amount>
    <ccy>643</ccy>
  </from>
  <to>
    <service-id>99</service-id>
    <amount>15.00</amount>
    <ccy>643</ccy>
    <account-number>79181234567</account-number>
  </to>
</payment>
<balances>
<balance code="428">0.00</balance>
<balance code="643">200</balance>
<balance code="840">12.20</balance>
</balances>
</response>
XML;*/

            $this->logResult($result);

            if (!$result) {
                throw new \Exception('Ошибка получения данных');
            }

            $xml = simplexml_load_string($result);

            if (!$xml) {
                throw new \Exception('Ошибка преобразования данных');
            }

            if (!isset($xml->payment->attributes()->{'result-code'})) {
                throw new \Exception('Ошибка получения параметров ответа');
            }

            $resultCode = $xml->payment->attributes()->{'result-code'}->__toString();

            if ($resultCode != 0) {
                throw new \Exception($xml->{'result-code'}->attributes()->message->__toString());
            }

            $response->status = $xml->payment->attributes()->status->__toString();
            $response->txn_id = $xml->payment->attributes()->txn_id->__toString();

            Yii::info("Вывод средств QIWI: (Куда: {$receiver}; Сумма: {$amount}; Транзакция: {$transactionID}; Транзакция в QIWI: {$response->txn_id}; Статус: {$response->status})", 'API');
        } catch (\Exception $e) {
            $response = new \stdClass();
            $response->success = false;
            $response->err_code = $e->getCode();
            $response->err_line = $e->getLine();
            $response->err_description = $e->getMessage();

            Yii::error("Ошибка вывода средств QIWI: " . print_r($response, true), 'API');
        }

        return $response;
    }

    private function logStart($transactionID, $type, $receiver, $amount)
    {
        $user = Yii::$app->user->identity;

        $this->log = new WithdrawalLog();
        $this->log->transaction = $transactionID;
        $this->log->user = json_encode([
            'id' => $user->id,
            'username' => $user->username,
            'driver_id' => $user->driver_id,
        ], JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT);
        $this->log->withdrawal_method_type = WithdrawalMethod::getNameById($type);
        $this->log->receiver = $receiver;
        $this->log->amount = (string) $amount;
        $this->log->date_create_operation = date('Y-m-d H:i:s');
        if (!$this->log->save()) {
            Yii::error("Ошибка сохранения WithdrawalLog::logStart в Qiwi::pay " . print_r($this->log, true), 'API');
        }
    }

    private function logParams($params)
    {
        $this->log->data = $params;
        if (!$this->log->save()) {
            Yii::error("Ошибка сохранения WithdrawalLog::logParams в Qiwi::pay " . print_r($this->log, true), 'API');
        }
    }

    private function logResult($result)
    {
        $this->log->response = $result;
        $this->log->date_complete_operation = date('Y-m-d H:i:s');
        if (!$this->log->save()) {
            Yii::error("Ошибка сохранения WithdrawalLog::logResult в Qiwi::pay " . print_r($this->log, true), 'API');
        }
    }
}