# yii-services

## YandexTaxi
### Инициализация
```php
/**
 * @param string $clientID 
 * @param string $apiKey ключ API
 * @param string $parkID идентификатор парка
 */
$yandexTaxi = new \app\services\YandexTaxi(
    $clientID,
    $apiKey,
    $parkID
);
```

###  Начисление/списание средств со счета
```php
/**
 * @param string $driver_id идентификатор водителя в Yandex. Пример: 423d34eb23b2c4ad47b196aad93f90b3
 * @param float $amount сумма. Если число отрицательное, то списывается
 */
$result = $yandexTaxi->pay($driver_id, $amount);
```

### Получение данных о водителе по номеру
```php
$result = $yandexTaxi->driverProfiles('+79999999999');
```

### Обработка ошибок
```php
if (!$result || (isset($result->code) && $result->code != 200)) {
    Yii::error($result);
}
```

---

## Qiwi
### Инициализация
```php
$qiwi = new \app\services\Qiwi();
```
Авторизационные данные подставлены из конфига в класс

###  Начисление средств на счет
```php
/**
 * @param int $id - идентификатор транзакции
 * @param int $type_id тип транзакции, карта или номер телефона
 * @param string $receiver номер карты или номер кошелька QIWI
 * @param float $amount сумма
 */
$result = $qiwi->pay($id, $type_id, $receiver, $amount);
```

###  Получение баланса
```php
$balance = $qiwi->balance();
```

---

## SmsRu
### Инициализация
```php
$smsRu = new \app\services\SmsRu();
```
Авторизационные данные подставлены из конфига в класс

### Отправка СМС
```php
$smsRu->send('+79999999999', 'Текст');
```
