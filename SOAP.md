# Руководство: как проектировать и применять SOAP-запросы на примере этого приложения

Это пошаговое объяснение для новичков. Мы разберём основы SOAP, WSDL, структуру SOAP-сообщений, а затем посмотрим, как это реализовано в проекте, как сформировать корректный запрос и получить ответ, как тестировать и отлаживать интеграции.

---

## 1) Что такое SOAP и как он работает

SOAP (Simple Object Access Protocol) — это протокол обмена сообщениями поверх XML. Ключевые идеи:

- Обмен идёт XML-сообщениями в формате «конверта» (Envelope), который может содержать:
  - Header — необязательный блок метаданных (аутентификация, транзакции и пр.)
  - Body — обязательный блок с фактическим запросом/ответом
- SOAP обычно работает поверх HTTP(S); тело HTTP-запроса — это XML SOAP Envelope
- Контракт описывается через WSDL (Web Services Description Language) — XML-документ, который формально определяет:
  - Какие операции доступны (порт/порт-тайп)
  - Какие входные/выходные сообщения у операций
  - Типы данных (через XSD)
  - Где опубликован сервис (endpoint)
- Стиль обмена в этом проекте: document/literal (самый распространённый и совместимый)

Полезно помнить: SOAP — строго типизированный обмен. Клиент и сервер должны соглашаться о структуре сообщений и типах по WSDL.

---

## 2) Где это в проекте

- WSDL: `public/wsdl/order.wsdl`
- SOAP endpoint (контроллер): `src/Controller/Api/v1/SoapController.php`
- Сервис, реализующий операции: `src/Application/Service/SoapOrderService.php`
- DTO для ответа: `src/Application/Dto/Soap/SoapOrderResponseDto.php`
- Юзкейс приложения: `src/Application/UseCase/CreateOrderUseCase.php` (используется сервисом)

Связь компонентов:
1. Клиент делает HTTP-запрос на `POST /soap` с SOAP Envelope в теле
2. Контроллер создаёт `SoapServer` и делегирует обработку объекту `SoapOrderService`
3. `SoapOrderService::createOrder` парсит входные параметры (по схеме из WSDL), собирает DTO и вызывает бизнес-логику (`CreateOrderUseCase`)
4. Результат возвращается в виде массива, который PHP SoapServer сериализует в SOAP XML согласно WSDL

Дополнительно: при `GET /soap` контроллер отдаёт сам WSDL — это удобно для генерации клиентов.

---

## 3) Разбор WSDL (контракт сервиса)

Файл: `public/wsdl/order.wsdl`
Главные разделы:
- `<types>` — XSD-схемы типов (в этом проекте описаны типы заказа и их список)
- `<message>` — сообщения запроса/ответа (ссылки на элементы из XSD)
- `<portType>` — абстрактный интерфейс операций (здесь: `createOrder`)
- `<binding>` — конкретизация способа обмена (soap:binding style="document")
- `<service>`/`<port>` — где доступен сервис (адрес endpoint)

Ключевые элементы для нашей операции:
- Вход: `CreateOrderRequest` содержит поля клиента и список позиций `articles`
- Выход: `CreateOrderResponse` содержит `success`, опциональные `order_id` и `message`
- Адрес: `<soap:address location="http://localhost:8000/soap"/>`

Если изменяете контракт (добавляете поля/операции), нужно:
1) Обновить XSD/тип элементов в `<types>`
2) Добавить/изменить `<message>` и `<portType>`
3) Прописать `<binding>/<operation>`
4) Реализовать/обновить соответствующий метод в `SoapOrderService`

---

## 4) Контроллер: точка входа

Файл: `src/Controller/Api/v1/SoapController.php`

Как работает:
- `GET /soap` — отдаёт WSDL как `text/xml` (клиентам удобно подтянуть контракт)
- `POST /soap` — создаёт `\SoapServer` с путём к WSDL, выключает кэш WSDL (`WSDL_CACHE_NONE`), подключает ваш объект-обработчик: `$soapServer->setObject($soapOrderService);`
- Затем `handle($request->getContent())` обрабатывает SOAP-запрос и возвращает ответ
- Ошибки отлавливаются, при исключениях возвращается стандартный SOAP Fault с кодом `Receiver`

Итого: адрес SOAP сервиса — тот же `/soap`.

---

## 5) Реализация операции `createOrder`

Файл: `src/Application/Service/SoapOrderService.php`

- Метод `createOrder($parameters): array`
  - Входной объект `$parameters` следует структуре `CreateOrderRequest` из WSDL
  - Список товаров (`articles->item`) может прийти как массив или как одиночный объект — код нормализует это к массиву
  - Каждая позиция преобразуется в `SoapOrderArticleDto`
  - Формируется `CreateOrderSoapRequestDto` и передаётся в `CreateOrderUseCase`
  - Ответ use-case возвращается как массив с ключами `success`, `order_id`, `message` — SoapServer сериализует это в SOAP XML по схеме ответа

Важно: приведение типов — строки/числа к ожидаемым типам (int, decimal, string). Это снижает шанс ошибок сериализации.

---

## 6) Пример SOAP-запроса и ответа

### Запрос (CreateOrderRequest)
Адрес: `POST http://localhost:8000/soap`
Заголовки: `Content-Type: text/xml; charset=utf-8`
Тело:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
                  xmlns:tns="http://localhost:8000/soap">
  <soapenv:Header/>
  <soapenv:Body>
    <tns:CreateOrderRequest>
      <client_name>Ivan</client_name>
      <client_surname>Ivanov</client_surname>
      <email>ivan@example.com</email>
      <pay_type>1</pay_type>
      <articles>
        <item>
          <article_id>1001</article_id>
          <amount>2.0</amount>
          <price>499.90</price>
          <weight>0.25</weight>
        </item>
        <item>
          <article_id>1002</article_id>
          <amount>1.0</amount>
          <price>1499.00</price>
          <weight>0.80</weight>
        </item>
      </articles>
    </tns:CreateOrderRequest>
  </soapenv:Body>
</soapenv:Envelope>
```

Примечания:
- Пространство имён `tns` должно совпадать с `targetNamespace` из WSDL (`http://localhost:8000/soap`)
- Порядок и имена элементов важны; типы должны соответствовать XSD (`integer`, `decimal`, `string`)
- Если только один `item`, его всё равно можно прислать как единственный блок `item` (без массива)

### Успешный ответ (CreateOrderResponse)
```xml
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
  <SOAP-ENV:Body>
    <ns1:CreateOrderResponse xmlns:ns1="http://localhost:8000/soap">
      <success>true</success>
      <order_id>12345</order_id>
      <message>Order created</message>
    </ns1:CreateOrderResponse>
  </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
```

### Ошибка (SOAP Fault пример)
```xml
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
  <SOAP-ENV:Body>
    <SOAP-ENV:Fault>
      <faultcode>SOAP-ENV:Receiver</faultcode>
      <faultstring>Validation failed: email is invalid</faultstring>
    </SOAP-ENV:Fault>
  </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
```

---

## 7) Как протестировать сервис

Вариант A: curl
```bash
curl -X POST \
  -H "Content-Type: text/xml; charset=utf-8" \
  --data @soap-create-order.xml \
  http://localhost:8000/soap
```
Где `soap-create-order.xml` — файл с телом SOAP Envelope (пример выше).

Вариант B: Postman
- Метод: POST
- URL: `http://localhost:8000/soap`
- Headers: `Content-Type: text/xml; charset=utf-8`
- Body: raw (XML) — вставьте полный Envelope

Вариант C: SoapUI
- File → New SOAP Project
- Укажите WSDL URL: `http://localhost:8000/soap` (GET вернёт сам WSDL)
- SoapUI поднимет операции и сгенерирует шаблоны запросов

Проверка WSDL:
```bash
curl http://localhost:8000/soap -H "Accept: text/xml"
```
Вы должны получить XML WSDL-файла.

---

## 8) Пример кода клиента

### PHP (SoapClient)
```php
<?php
$wsdl = 'http://localhost:8000/soap'; // контроллер отдаёт WSDL по GET
$options = [
    'trace' => 1,
    'cache_wsdl' => WSDL_CACHE_NONE,
];
$client = new SoapClient($wsdl, $options);

$request = [
    'client_name' => 'Ivan',
    'client_surname' => 'Ivanov',
    'email' => 'ivan@example.com',
    'pay_type' => 1,
    'articles' => [
        'item' => [
            [
                'article_id' => 1001,
                'amount' => 2.0,
                'price' => 499.90,
                'weight' => 0.25,
            ],
            [
                'article_id' => 1002,
                'amount' => 1.0,
                'price' => 1499.00,
                'weight' => 0.80,
            ],
        ],
    ],
];

$response = $client->__soapCall('createOrder', [['parameters' => $request]]);
var_dump($response);
```

### Python (zeep)
```python
from zeep import Client

client = Client('http://localhost:8000/soap')
req = {
    'client_name': 'Ivan',
    'client_surname': 'Ivanov',
    'email': 'ivan@example.com',
    'pay_type': 1,
    'articles': {
        'item': [
            {'article_id': 1001, 'amount': 2.0, 'price': 499.90, 'weight': 0.25},
            {'article_id': 1002, 'amount': 1.0, 'price': 1499.00, 'weight': 0.80},
        ]
    }
}
resp = client.service.createOrder(parameters=req)
print(resp)
```

---

## 9) Частые ошибки и как их избежать

- Пространства имён (namespaces): в Envelope используйте `xmlns:tns` из `targetNamespace` WSDL
- Типы данных: `integer` vs `decimal` — не отправляйте числа как строки, если клиент это не ожидает; на сервере приводите типы, как в `SoapOrderService`
- Массивы vs одиночный элемент: в SOAP с XSD `maxOccurs="unbounded"` клиентские библиотеки часто отправляют одиночный `item` без массива; серверный код должен уметь распознать обе формы (в проекте это учтено)
- Content-Type: используйте `text/xml; charset=utf-8`
- Кэш WSDL: отключите на время разработки (`WSDL_CACHE_NONE`), иначе изменения WSDL могут не подтянуться у клиента
- Faults: любые неперехваченные исключения превратятся в SOAP Fault; при интеграции логируйте тело запроса и ответ для диагностики (`trace` в PHP SoapClient)

---

## 10) Как добавить новую SOAP-операцию (чек-лист)

1) В `public/wsdl/order.wsdl`:
   - В `<types>` опишите XSD-элементы запроса/ответа
   - В `<message>` добавьте сообщения запроса/ответа
   - В `<portType>` добавьте `<operation name="...">`
   - В `<binding>` пропишите `<operation>` с `soap:operation` и телами input/output (`use="literal"`)
2) В `SoapOrderService` реализуйте публичный метод с тем же именем, что и `<operation name>` (например, `public function getOrderStatus($parameters): array`)
3) Внутри метода:
   - Разберите `$parameters` согласно XSD
   - Приведите типы, соберите DTO
   - Вызовите соответствующий UseCase и верните массив результата с ключами, совпадающими с элементами ответа в XSD
4) Перезапустите клиент/очистите кэш WSDL у клиента
5) Протестируйте через curl/Postman/SoapUI

---

## 11) Безопасность и эксплуатация

- Ограничьте доступ к `/soap` (IP allow-list, аутентификация на уровне веб-сервера/прокси)
- Включите логирование входящих XML и ответов в non-PII-виде для отладки
- Валидируйте входные данные (email, типы, границы чисел) в use-case/валидаторах
- Следите за версиями WSDL: при несовместимых изменениях публикуйте новый endpoint или версионируйте namespace

---

## 12) Краткий FAQ

- Где взять WSDL URL? — `GET http://localhost:8000/soap`
- Как назвать метод в сервисе? — как `<operation name>` в WSDL (`createOrder`)
- Что возвращать из метода? — массив с ключами как в XSD ответа (`success`, `order_id`, `message`)
- Почему не видна новая операция? — клиент кэширует WSDL; отключите кэш, перезапустите клиента
- Что делать с одиночным `item` в массивах? — см. пример в `SoapOrderService`: приводите к массиву

---

Готово. Теперь вы можете уверенно проектировать и вызывать SOAP-операции в рамках этого приложения: от обновления WSDL до реализации метода и тестирования запросов.
