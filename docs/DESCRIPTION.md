# Техническое описание приложения JTE Test

Это руководство предназначено для программистов, желающих детально разобраться в архитектуре, технологиях и алгоритмах, используемых в приложении для управления заказами и поиска.

---

## 🏗 Архитектура приложения

Приложение построено на принципах **Clean Architecture**, что позволяет отделить бизнес-логику от деталей реализации (БД, API, внешние сервисы).

### Слои приложения:
1.  **Domain (Доменный слой)** (`src/Domain`): Самый глубокий слой. Содержит сущности (`Entity`), интерфейсы репозиториев, DTO для бизнес-логики и доменные правила. Не зависит от внешних библиотек (кроме стандартных PHP).
2.  **Application (Прикладной слой)** (`src/Application`): Реализует Use Cases (варианты использования). Здесь находятся DTO для API, кастомные валидаторы (например, `BatchEntityExists` для эффективной проверки существования сущностей в БД) и логика, управляющая потоком данных.
3.  **Infrastructure (Инфраструктурный слой)** (`src/Infrastructure`): Реализация интерфейсов. Здесь живет Doctrine ORM (включая `TransactionManager`), механизмы Manticore Search, интеграции с Prometheus и настройки Symfony.
4.  **Presentation (Слой представления)** (`src/Controller`): Обработка входящих запросов (REST, SOAP). Содержит контроллеры и интеграцию с SOAP-сервером.

---

## 📡 Описание API и методов

Приложение предоставляет два типа интерфейсов: REST и SOAP.

### 1. REST API (`src/Controller/Api/v1/OrderController.php`)

*   **GET `/api/v1/orders/{id}`** — Детальная информация о заказе.
    *   **Цепочка вызовов**: `OrderController::getOrder` -> `GetOrderUseCase::execute` -> `OrderRepository::findById`.
    *   **Логика обработки**:
        1. `GetOrderUseCase` запрашивает заказ из БД через репозиторий. Если заказ не найден — выбрасывается `NotFoundHttpException` (404).
        2. Полученная сущность `Order` преобразуется в `OrderResponseDto`.
        3. В контроллере (`OrderController`) на основе данных DTO (ID и дата создания) вычисляется **ETag** и устанавливается заголовок **Last-Modified**.
        4. Вызывается `$response->isNotModified($request)`. Если клиент прислал валидный ETag в `If-None-Match` или дату в `If-Modified-Since`, Symfony прерывает выполнение и возвращает **304 Not Modified** без тела ответа.
        5. Если данные изменились, DTO сериализуется в JSON и отправляется с кодом 200.
*   **GET `/api/v1/orders/search`** — Полнотекстовый поиск.
    *   **Цепочка вызовов**: `OrderController::search` -> `SearchOrdersUseCase::execute` -> `FallbackSearchProvider::search`.
    *   **Логика обработки**:
        1. Контроллер использует `MapQueryString` для автоматической валидации параметров запроса в `OrderSearchRequestDto`.
        2. Перед поиском проверяется **ETag**, который зависит от параметров запроса и метки последнего обновления данных в БД (`order_last_update_timestamp` из Redis).
        3. Поиск выполняется через цепочку декораторов (**Resilience Layer**):
            - `FallbackSearchProvider` координирует работу основного и резервного провайдеров.
            - `CircuitBreakerSearchProvider` защищает основной поиск (`ManticoreSearchProvider`) с помощью компонента `CircuitBreaker`.
            - Если `CircuitBreaker` "размыкает цепь" (состояние OPEN) или Manticore возвращает ошибку, `FallbackSearchProvider` перенаправляет запрос в `DatabaseSearchProvider` (прямой поиск в MySQL).
        4. Результаты (найденные ID и метаданные) возвращаются в виде `SearchResultDto`.
*   **GET `/api/v1/orders/stats`** — Статистика по заказам.
    *   **Цепочка вызовов**: `OrderController::getStats` -> `GetOrderStatsUseCase::execute` -> `OrderStatsProviderInterface`.
    *   **Логика обработки**:
        1. Аналогично поиску, используется **ETag** на базе метки последнего обновления данных.
        2. `GetOrderStatsUseCase` запрашивает агрегированные данные (количество заказов и суммы) с группировкой по периоду (день/месяц/год).
        3. Результаты кешируются в Redis с тегом `stats`.
        4. Данные маппятся в `OrderStatsDto` и возвращаются клиенту.
*   **GET `/api/v1/price`** — Получение цены товара с внешнего сайта.
    *   **Цепочка вызовов**: `PriceController::getPrice` -> `GetPriceUseCase::execute` -> `CachedPriceParserDecorator::parse` -> `PriceParser::parse`.
    *   **Логика обработки**:
        1. `PriceController` извлекает параметры `factory`, `collection`, `article` из GET-запроса.
        2. `GetPriceUseCase` делегирует выполнение интерфейсу `PriceParserInterface`.
        3. `CachedPriceParserDecorator` сначала ищет цену в **Redis** (ключ формируется на основе MD5-хэшей параметров).
        4. Если цены нет в кэше, проверяется **Circuit Breaker** (предохранитель). Если количество ошибок превысило порог, парсер не вызывается.
        5. Если сервис доступен, `PriceParser` делает HTTP-запрос к внешнему ресурсу (например, `tile.expert`) и извлекает цену с помощью `Symfony DomCrawler` и регулярных выражений.
        6. Успешный результат кэшируется на 1 час.
        7. Если внешний сайт недоступен, декоратор записывает ошибку в Circuit Breaker и пытается вернуть ранее кэшированное значение (даже если оно устарело) или выбрасывает исключение, предотвращая лавинообразную нагрузку на систему при сбоях внешнего сервиса.

---

## 📊 Мониторинг и логирование (Prometheus & Sentry)

В приложении реализована продвинутая система мониторинга на базе **Prometheus** (метрики) и **Sentry** (ошибки и трейсинг).

---

## 🚀 CI/CD и Deployment

Для обеспечения качества и автоматизации развертывания настроен CI/CD пайплайн через **GitHub Actions**.

### Этапы CI/CD:
1.  **Build & Test** (настройка в `.github/workflows/ci-cd.yml`):
    *   Установка PHP зависимостей (`composer install`).
    *   Статический анализ кода (`PHPStan`, конфиг `phpstan.neon`).
    *   Проверка безопасности зависимостей (`Symfony Security Checker`).
    *   Запуск Unit и Integration тестов (`PHPUnit`, конфиг `phpunit.dist.xml`).
2.  **Package** (сборка Docker-образа):
    *   Сборка оптимизированного Docker-образа для Production (`docker/php/Dockerfile.prod`).
    *   Используется multi-stage build для минимизации размера образа и исключения dev-зависимостей.
    *   Пуш образа в **GitHub Container Registry (GHCR)**.
3.  **Deploy** (развертывание через Helm):
    *   Развертывание в кластер **Kubernetes** с использованием **Helm** (конфигурация в `helm/charts/jte-test`).
    *   Автоматическое обновление версии образа.
    *   Обеспечение Zero Downtime Deployment.

### Инфраструктура в Kubernetes (Helm):
Все шаблоны и настройки инфраструктуры находятся в директории `helm/charts/jte-test`:
*   **Deployment & Service** (`templates/deployment.yaml`, `templates/service.yaml`): Описание подов приложения и доступа к ним.
*   **Horizontal Pod Autoscaler (HPA)** (`templates/hpa.yaml`): Автоматическое масштабирование количества подов (от 3 до 10) в зависимости от нагрузки на CPU и Memory.
*   **Resource Quotas**: Четкие лимиты и запросы ресурсов для стабильности кластера (настроены в `values.yaml`).
*   **Liveness & Readiness Probes**: Автоматическое обнаружение и перезапуск зависших контейнеров, а также исключение неготовых подов из балансировки трафика.
*   **ConfigMaps & Secrets** (`templates/configmap.yaml`, `templates/secrets.yaml`): Разделение конфигурации и чувствительных данных (DB_URL).
*   **Nginx Ingress** (`templates/ingress.yaml`): Управление входящим трафиком и TLS-терминация.

---

### 1. Prometheus: Написание новых метрик

Для сбора метрик используется бандл `artprima/prometheus-metrics-bundle`.

#### Как добавить новую метрику:
1.  **Создайте класс коллектора**:
    Класс должен находиться в `src/Infrastructure/Prometheus/` и реализовывать `MetricsCollectorInterface`.
    ```php
    namespace App\Infrastructure\Prometheus;

    use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInitTrait;
    use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInterface;
    use Artprima\PrometheusMetricsBundle\Metrics\PreRequestMetricsCollectorInterface;
    use Symfony\Component\HttpKernel\Event\RequestEvent;

    class MyCustomCollector implements MetricsCollectorInterface, PreRequestMetricsCollectorInterface
    {
        use MetricsCollectorInitTrait;

        public function collectStart(RequestEvent $event): void
        {
            // Регистрация и установка значения метрики
            $gauge = $this->collectionRegistry->getOrRegisterGauge(
                $this->namespace,
                'my_custom_metric_name',
                'Описание метрики',
                ['label_name'] // список имен меток
            );
            $gauge->set(123.45, ['label_value']);
        }
    }
    ```
2.  **Зарегистрируйте коллектор в `config/services.yaml`**:
    ```yaml
    App\Infrastructure\Prometheus\MyCustomCollector:
        tags: ['prometheus_metrics_bundle.metrics_collector']
        arguments:
            $namespace: '%prometheus_metrics_bundle.namespace%'
    ```
3.  **Типы метрик**:
    *   `Counter` — только увеличивается (например, количество запросов).
    *   `Gauge` — может увеличиваться и уменьшаться (например, использование памяти).
    *   `Histogram` / `Summary` — для распределений (например, длительность запроса).

#### Использование в бизнес-логике (incremental metrics):
Если нужно зафиксировать событие глубоко в логике (например, "заказ создан"), рекомендуется использовать временное хранилище (Redis/Cache), так как Prometheus в PHP работает по модели "scrape", собирая данные в момент запроса к `/metrics`.
Пример (как в `DomainMetricsCollector`):
1. В коде: `$cache->get('my_counter', fn() => 0); $cache->delete('my_counter'); $cache->get('my_counter', fn() => $val + 1);`
2. В коллекторе: считать значение из кэша, вызвать `$counter->incBy($val)`, очистить кэш.

#### Grafana:
- Метрики доступны по адресу `/metrics`.
- Prometheus настроен на сбор данных с этого эндпоинта (см. `docker/prometheus/prometheus.yml`).
- В Grafana используйте **PromQL** для построения графиков. Пример: `sum(rate(app_http_requests_total[5m])) by (status)`.

---

### 4. Мониторинг ошибок и производительности (Sentry)
Приложение интегрировано с **Sentry** для автоматического отслеживания сбоев и анализа быстродействия.

**Что можно проверять и отслеживать через Sentry:**

*   **Ошибки (Exceptions)**:
    *   **HTTP-исключения**: Автоматически перехватываются все необработанные исключения в контроллерах. Даже если `ExceptionListener` превращает их в JSON-ответ, Sentry фиксирует событие до обработки слушателем.
    *   **Консольные ошибки**: Ошибки при выполнении CLI-команд (например, `app:outbox:process`) отправляются в Sentry.
    *   **Ошибки Messenger**: Если обработчик сообщения (например, `SendOrderEmailHandler`) падает, Sentry фиксирует это вместе с контекстом сообщения (класс сообщения, очередь).
*   **Производительность (Performance & Tracing)**:
    *   **Трассировка запросов (Transactions)**: Можно видеть полные цепочки вызовов (Spans) для каждого HTTP-запроса.
    *   **SQL-запросы**: Sentry интегрирован с Doctrine и логирует время выполнения каждого запроса. Это помогает находить N+1 запросы и медленные выборки.
    *   **Внешние HTTP-вызовы**: Фиксируется время ответа внешних сервисов (например, при парсинге цен через `HttpClient`).
*   **Контекст и отладка**:
    *   **Breadcrumbs (Хлебные крошки)**: Sentry записывает последовательность событий, предшествующих ошибке (логи, SQL-запросы, HTTP-запросы).
    *   **Trace ID**: Приложение прокидывает 128-битный идентификатор в заголовках. По нему можно найти конкретное событие в Sentry, сопоставив его с логами или жалобой пользователя.
    *   **User Context**: Если пользователь аутентифицирован, его ID автоматически привязывается к отчету.

**Настройка и использование:**

1.  **Настройка**:
    - **DSN**: Укажите в `.env` переменную `SENTRY_DSN`.
    - **Конфиг**: `config/packages/sentry.yaml`. Включены `register_error_listener` (авто-отлов исключений), `tracing` (отслеживание производительности) и `messenger` (ошибки в очередях).

2.  **Добавление нового события или данных (через `HubInterface`):**
    Для ручного управления событиями используйте `Sentry\State\HubInterface`.

    - **Добавление контекста и тегов (Scope):**
      Используйте `configureScope`, чтобы добавить метаданные к *будущим* событиям в рамках текущего запроса:
      ```php
      use Sentry\State\Scope;
      use Sentry\State\HubInterface;

      public function someMethod(HubInterface $hub): void {
          $hub->configureScope(function (Scope $scope): void {
              $scope->setTag('order_type', 'wholesale'); // Теги (индексируются, по ним можно искать)
              $scope->setContext('order', ['id' => 123, 'total' => 5000]); // Контекст (доп. данные)
              $scope->setUser(['id' => 'user_456']); // Привязка к пользователю
          });
      }
      ```

    - **Ручной захват исключений (captureException):**
      Если вы перехватываете исключение в `try-catch`, но всё равно хотите отправить его в Sentry:
      ```php
      try {
          // какой-то код
      } catch (\Throwable $e) {
          $hub->captureException($e);
          // обработка ошибки
      }
      ```

    - **Отправка текстовых сообщений (captureMessage):**
      Для логирования важных событий без исключений:
      ```php
      use Sentry\Severity;

      $hub->captureMessage('Заказ был отменен вручную администратором', Severity::info());
      ```

3.  **Логирование через Monolog**:
    Любой лог уровня `ERROR` и выше автоматически отправляется в Sentry (если настроен соответствующий хендлер в `monolog.yaml`).
    ```php
    $this->logger->error('Something went wrong', ['order_id' => 123]);
    ```

4.  **Трейсинг производительности (Transactions)**:
    Для замера длительности специфического блока кода:
    ```php
    $transactionContext = \Sentry\Tracing\TransactionContext::make()
        ->setName('Heavy Calculation')
        ->setOp('calc');
    $transaction = \Sentry\startTransaction($transactionContext);

    // ... ваш код ...

    $transaction->finish();
    ```

**Как проверить работу:**
Выполните команду в контейнере для отправки тестового события:
```bash
docker-compose exec php bin/console sentry:test
```

---

### 2. SOAP API (`src/Controller/Api/v1/SoapController.php`)

*   **Endpoint**: `/soap`
*   **WSDL**: Доступен по `GET /soap?wsdl`. Генерируется через `WsdlProvider`.
*   **Метод `createOrder`**:
    *   **Цепочка вызовов**: `SoapController::index` -> `SoapOrderService::createOrder` -> `CreateOrderUseCase::execute`.
    *   **Логика обработки**:
        1. `SoapServer` принимает XML, `SoapConverter` десериализует его в `CreateOrderSoapRequestDto`.
        2. `SoapOrderService` валидирует входящие данные через `Symfony Validator`. В процессе валидации используется кастомное ограничение `BatchEntityExists`, которое эффективно (одним запросом) проверяет существование типов оплаты и товаров в БД. При ошибках выбрасывается `SoapFault` с детальным описанием полей (ключ `errors`).
        3. `CreateOrderUseCase` выполняет создание заказа внутри **БД-транзакции** (`TransactionManagerInterface`):
            - `OrderFactory` создает сущность `Order` и связанные объекты на основе доменного DTO.
            - `OrderRepository::save($order)` сохраняет данные в MySQL.
            - В этой же транзакции срабатывает `DomainEventListener`, который видит событие `OrderCreatedEvent` и создает запись в таблице `outbox_events` (паттерн **Transactional Outbox**).
        4. Если транзакция успешна, возвращается `SoapOrderResponseDto` с ID нового заказа.
        5. Фоновый процесс (описан в разделе "Технологии") позже обработает Outbox-запись и отправит Email.

---

## ⚡️ Технологии для работы под нагрузкой

Приложение спроектировано так, чтобы выдерживать высокие нагрузки, используя следующие приемы:

### 1. Transactional Outbox (Гарантированная отправка уведомлений)
Чтобы не замедлять создание заказа и гарантировать отправку Email (даже если почтовый сервер временно недоступен), используется паттерн **Transactional Outbox**.
*   **Где реализовано**: `DomainEventListener.php`, `ProcessOutboxCommand.php`, `SendOrderEmailHandler.php`.
*   **Как работает**: 
    1. В одной транзакции с сохранением заказа в таблицу `outbox_events` записывается событие `EMAIL_NOTIFICATION`. Это гарантирует, что если заказ сохранился — событие тоже сохранится.
    2. Фоновая команда `app:outbox:process` считывает необработанные события и отправляет их в асинхронную очередь Symfony Messenger.
    3. Обработчик `SendOrderEmailHandler` берет сообщение из очереди и выполняет реальную отправку.
*   **Зачем**: Это исключает риск "потерянных" писем и не заставляет пользователя ждать, пока ответит внешний SMTP-сервер.

### 2. Resilience Layer и Circuit Breaker (Отказоустойчивость)
Разделение ответственности за стабильность системы реализовано через декораторы в `src/Infrastructure/Search` и универсальный компонент `src/Infrastructure/Resilience`.
*   **Зачем**: Чтобы сбои в одном компоненте (например, Manticore Search) не приводили к отказу всего API, и чтобы защитить внешние ресурсы от излишней нагрузки при их нестабильности.
*   **Как работает**: 
    1.  **Fallback**: `FallbackSearchProvider` пробует выполнить поиск в основном источнике. Если он недоступен — прозрачно для пользователя переключается на MySQL.
    2.  **Circuit Breaker**: `CircuitBreaker` (Предохранитель) отслеживает количество ошибок. После N неудач подряд (`failureThreshold`) он "разрывает цепь" и сразу возвращает ошибку (или активирует fallback), не пытаясь достучаться до упавшего сервиса в течение времени восстановления (`recoveryTime`).
*   **Где используется**:
    - В поиске заказов (цепочка `Fallback` -> `CircuitBreaker` -> `Manticore`).
    - В парсинге цен (`CachedPriceParserDecorator`).

### 3. Zero-Downtime переиндексация
Реализована в `OrderReindexer.php`.
*   **Проблема**: При обновлении миллионов записей в поиске индекс может быть недоступен или выдавать неполные данные.
*   **Решение**: Создается временный индекс (`orders_tmp_...`), в него заливаются данные пачками (batching). После завершения Manticore атомарно переключает (swap) основной индекс на новый. Весь процесс незаметен для пользователя.

### 4. HTTP-кэширование и ETag
В `OrderController.php` для каждого запроса вычисляется контрольная сумма (ETag) на основе даты последнего обновления данных.
*   Если клиент присылает заголовок `If-None-Match` с тем же ETag, Symfony возвращает пустой ответ с кодом 304.

### 5. Асинхронная обработка (Messenger)
Используется компонент Symfony Messenger для выноса тяжелых задач в фон:
*   **Отправка уведомлений**: После обработки Outbox-события.
*   **Инвалидация кэша статистики**: `InvalidateStatsCacheHandler` сбрасывает тег `stats` асинхронно после любого изменения в заказах. Это гарантирует актуальность данных в API статистики, не замедляя основной процесс сохранения заказа.

### 6. Многоуровневая инвалидация кэша
Для обеспечения согласованности данных (Data Consistency) используются два механизма:
1.  **Синхронный (`OrderStatsSubscriber`)**: При создании нового заказа (`postPersist`) сразу обновляет таблицу агрегатов `order_stats` и сбрасывает кэш тега `stats`.
2.  **Асинхронный (`DomainEventListener` + Messenger)**: При любых изменениях (update/delete) через шину сообщений отправляется `InvalidateStatsCacheMessage`. Это позволяет избежать задержек при массовых изменениях данных.

### 7. Глобальная метка времени изменений
Для работы HTTP-кэширования (ETag) в Redis хранится ключ `order_last_update_timestamp`. 
*   **Обновление**: `DomainEventListener` при каждом успешном `postFlush` обновляет это значение текущей микросекундой.
*   **Использование**: Контроллеры используют эту метку для генерации ETag. Если данные в БД не менялись с последнего запроса клиента, сервер вернет **304 Not Modified**, экономя трафик и ресурсы на генерацию JSON.

---

## 🛡 Обработка ошибок и трассировка

В приложении реализована централизованная система обработки ошибок и трассировки запросов.

### 1. Обработка исключений в REST API
Для REST API используется `ExceptionListener.php`, который перехватывает все исключения в рамках HTTP-запроса и преобразует их в унифицированный JSON-ответ.
*   **Формат ответа**:
    ```json
    {
      "error": "Сообщение об ошибке",
      "violations": { "field_name": "причина ошибки" }
    }
    ```
*   **ValidationException**: Специальное исключение для ошибок валидации DTO. Возвращает HTTP 422 и список нарушений в поле `violations`.
*   **HttpException**: Стандартные исключения Symfony (404 Not Found, 403 Forbidden и т.д.) автоматически маппятся на соответствующие HTTP-коды.
*   **Internal Server Error**: Любое необработанное исключение возвращается с кодом 500 и общим сообщением, скрывая детали реализации в продакшене.

### 2. Обработка ошибок в SOAP
В `SoapController.php` все ошибки перехватываются и возвращаются клиенту в формате **SoapFault** (XML). Это гарантирует, что SOAP-клиент сможет корректно обработать сбой на уровне протокола.

### 3. Трассировка запросов (Trace ID и Request ID)
Для отслеживания пути запроса через логи и систему мониторинга используется двойной механизм идентификации:
*   **Trace ID (128 бит)**: Генерируется `TraceIdContext`. Предназначен для сквозной трассировки (Distributed Tracing). Передается в заголовке `X-Trace-ID` ответа и SOAP-ошибках.
*   **Request ID**: Генерируется `RequestIdListener` (или берется из заголовка `X-Request-Id`). Используется для идентификации конкретного HTTP-запроса внутри приложения.
*   **Логирование**: Оба идентификатора автоматически подмешиваются в контекст логов. Это позволяет найти все связанные события в логах (например, через ELK или Grafana Loki) по любому из идентификаторов.


---

## 🛠 Инструменты отладки и разработки

### 1. Xdebug 3

Для глубокой отладки PHP-кода в контейнере предустановлен Xdebug.

*   **Конфигурация (`docker/php/xdebug.ini`):**
    *   `xdebug.mode=debug` — включен полнофункциональный отладчик.
    *   `xdebug.client_port=9003` — стандартный порт для Xdebug 3.
    *   `xdebug.start_with_request=yes` — попытка запуска отладки при каждом запросе.
    *   `xdebug.discover_client_host=yes` — автоматическое определение IP хоста (полезно для Linux/Docker Desktop).

*   **Интеграция с Docker (`docker-compose.yml`):**
    *   `XDEBUG_CONFIG: "client_host=host.docker.internal"` — принудительный адрес для соединения с IDE.
    *   `PHP_IDE_CONFIG: "serverName=jte-test"` — имя сервера, которое IDE должна использовать для маппинга путей.
    *   `extra_hosts: ["host.docker.internal:host-gateway"]` — проброс IP хоста внутрь контейнера.

---

## 📊 Мониторинг и метрики

Для отслеживания состояния системы используются **Prometheus** и **Grafana**.

### Используемые технологии:
*   **Prometheus**: База данных временных рядов, которая собирает метрики.
*   **Grafana**: Панель визуализации графиков.
*   **Redis**: Используется как промежуточное хранилище для некоторых метрик.

### Сбор и состав метрик:

В приложении настроен сбор четырех групп метрик: **бизнес-метрики**, **производительность БД и очередей**, **системные метрики (Node)** и **статистика HTTP-трафика**.

#### 1. Бизнес-метрики (Domain Metrics)
Позволяют отслеживать активность пользователей и выполнение ключевых бизнес-процессов.
*   `app_orders_created_total` — Общее количество созданных заказов. Инкрементируется в `DomainEventListener` при успешном сохранении сущности `Order` в БД.
*   `app_emails_sent_total` — Общее количество отправленных писем. Инкрементируется в `SendOrderEmailHandler` после успешного вызова `MailerInterface`.

#### 2. Производительность БД и очередей (Infrastructure Metrics)
Критические показатели стабильности инфраструктуры.
*   `app_database_response_time_seconds` — Время отклика соединения с БД (замер `SELECT 1`). Собирается `DatabasePerformanceCollector` при каждом запросе.
*   `app_doctrine_flush_duration_seconds` — Время выполнения `EntityManager::flush()`. Позволяет выявлять "тяжелые" транзакции. Собирается `DoctrineFlushCollector`.
*   `app_messenger_queue_messages` — Количество сообщений в очереди `messenger_messages`. Подсчитывается фоновой командой `CollectMessengerStatsCommand` и отдается через `MessengerQueueCollector`.

#### 3. Статистика кэша и HTTP (Traffic Metrics)
Позволяет оценивать эффективность кэширования и нагрузку на API.
*   `app_http_requests_total` — Счетчик всех входящих HTTP-запросов.
*   `app_http_errors_total` — Счетчик запросов, завершившихся с ошибкой (код >= 400). Позволяет вычислять **Error Rate**.
*   `app_http_request_duration_seconds` — Длительность обработки запросов (квантили p50, p95, p99).
    *   *Реализация*: Все три HTTP-метрики собираются через `HttpRequestSubscriber`, слушающий события `kernel.request` и `kernel.terminate`.
*   `app_redis_cache_hits_total` / `app_redis_cache_misses_total` — Статистика эффективности Redis.
    *   *Реализация*: Используется `CacheMetricsTrait`, интегрированный в декораторы репозиториев и парсер цен. Это гарантирует учет попаданий даже при использовании сложных цепочек декораторов или Sentry.

#### 4. Системные метрики (Node Metrics)
Мониторинг ресурсов контейнера без использования внешних агентов (node_exporter). Собираются `SystemMetricsCollector`.
*   `app_node_cpu_seconds_total` — Потребление процессорного времени по режимам (user, system, idle и т.д.). Данные берутся из `/proc/stat`.
*   `app_node_memory_available_bytes` — Объем доступной оперативной памяти в контейнере. Данные из `/proc/meminfo`.

### Трассировка и визуализация:
*   **Grafana**: Все метрики выведены на дашборд `Extended Metrics`, включающий графики Error Rate, Latency (p99), интенсивность заказов и состояние очередей.
*   **Трассировка**: Уникальные `Trace ID` позволяют коррелировать аномалии на графиках (например, всплеск `doctrine_flush_duration`) с конкретными запросами в логах и ошибками в Sentry.

---

## 🛠 Технологический стек
*   **PHP 8.4**: Используются современные возможности языка (Readonly-классы, перечисления, атрибуты).
*   **Symfony 8.0**: Основа приложения.
*   **MySQL 8.0**: Основное хранилище данных.
*   **Manticore Search 6.2**: Движок для быстрого полнотекстового поиска.
*   **Redis**: Кэширование данных и метрик.
*   **Mailpit**: Локальный SMTP-сервер с веб-интерфейсом для перехвата и просмотра исходящих писем. В разработке используется вместо реального почтового сервера (`MAILER_DSN=smtp://mailpit:1025`), чтобы письма не улетали реальным пользователям.
*   **Docker**: Вся инфраструктура описана в `docker-compose.yml`, что гарантирует идентичность окружения на разработке и продакшене.
