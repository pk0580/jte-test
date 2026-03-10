# JTE Test App (Symfony API)

Приложение на **Symfony 8.0** и **PHP 8.4** для управления заказами, полнотекстового поиска и мониторинга цен.

## 🚀 Основные технологии

- **PHP 8.4** (FPM)
- **Symfony 8.0** (Skeleton)
- **MySQL 8.0** — основная реляционная БД.
- **Manticore Search 6.2** — высокопроизводительный полнотекстовый поиск.
- **Redis** — кэширование и хранение метрик.
- **Prometheus & Grafana** — сбор и визуализация метрик (приложения, БД, очередей).
- **Docker & Docker Compose** — полная инфраструктура в контейнерах.

## 🏗 Архитектура

Проект реализован с использованием принципов **Clean Architecture**:
- `src/Domain` — бизнес-логика: сущности, интерфейсы репозиториев, доменные события.
- `src/Application` — прикладной слой: Use Cases, DTO, сервисы, обработчики команд.
- `src/Infrastructure` — реализация интерфейсов: Doctrine ORM, Manticore Search, внешние парсеры, контроллеры, SOAP-сервер.

---

## 🛠 Установка и запуск

### 1. Подготовка окружения
```bash
cp .env .env.local
```
Отредактируйте `.env.local` (настройте порты и доступы к БД, если необходимо).
Убедитесь, что порты `8080`, `3306`, `6379`, `9306` свободны.

### 2. Запуск контейнеров
```bash
docker-compose up -d
```

### 3. Настройка приложения
```bash
# Установка зависимостей
docker-compose exec php composer install

# Применение миграций и наполнение справочников
docker-compose exec php bin/console doctrine:migrations:migrate --no-interaction

# Начальная индексация данных в Manticore Search
docker-compose exec php bin/console app:index-orders
```

---

## 📡 API Методы

### Документация
- **Swagger UI**: `GET /api/doc` — интерактивная документация REST API.
- **OpenAPI JSON**: `GET /api/doc.json`.
- **SOAP WSDL**: `GET /soap?wsdl`.

### REST API (v1)
- `GET /api/v1/orders/{id}` — получение детальной информации о заказе по ID.
- `GET /api/v1/orders/search` — полнотекстовый поиск заказов через Manticore Search.
    - Параметры: `query`, `page`, `limit`, `status`.
- `GET /api/v1/orders/stats` — статистика заказов со сгруппировкой (day, month, year).
- `GET /api/v1/price` — получение актуальной цены (парсинг внешних источников).
    - Параметры: `factory`, `collection`, `article`.
- `GET /api/v1/health` — проверка работоспособности сервисов (Manticore, DB).

### SOAP API
Эндпоинт: `/soap`
- Метод `createOrder` — создание нового заказа. Описание типов данных доступно в WSDL.

---

## 🧪 Тестирование и качество кода

В проекте используется комплексный подход к тестированию:

### 1. Запуск тестов (PHPUnit)
```bash
docker-compose exec php composer test
# или напрямую
docker-compose exec php vendor/bin/phpunit
```
Включает: **Unit**, **Integration** и **Functional** (API) тесты.

### 2. Статический анализ (PHPStan)
Проверка типов и потенциальных ошибок:
```bash
docker-compose exec php composer phpstan
```

### 3. Проверка стиля кода (ECS)
```bash
docker-compose exec php composer ecs
# Исправить автоматически:
docker-compose exec php composer ecs:fix
```

### 4. Архитектурные тесты (PHPArkitect)
Проверка соблюдения правил Clean Architecture (зависимости между слоями):
```bash
docker-compose exec php composer arkitect
```

---

## 📊 Мониторинг и фоновые задачи

### Сбор метрик
Для работы дашбордов Grafana необходимо регулярно собирать метрики и иметь данные в БД.

**Описание метрик:**
- `app_messenger_queue_messages` — количество сообщений в очереди (таблица `messenger_messages`). Позволяет отслеживать нагрузку на фоновые задачи и задержки обработки.
- `app_database_response_time_seconds` — время ответа базы данных при выполнении контрольного запроса (`SELECT 1`). Используется для мониторинга доступности и сетевых задержек между PHP и MySQL.
- `app_doctrine_flush_duration_seconds` — длительность процесса `flush` в Doctrine ORM. Помогает выявить тяжелые транзакции и проблемы при сохранении сущностей в БД.

**Генерация тестовых данных:**
```bash
docker-compose exec php bin/console app:generate-sample-data
```

**Сбор статистики очередей:**
```bash
docker-compose exec php bin/console app:collect-messenger-stats
```
**Настройка Cron:** рекомендуется запускать каждые 10-60 секунд.

**Проверка метрик в Prometheus:**
После генерации данных перейдите на `http://localhost:9090` и выполните запрос:
- `app_messenger_queue_messages`
- `app_database_response_time_seconds`
- `app_doctrine_flush_duration_seconds`

### Manticore Search
- `app:index-orders` — полная переиндексация с поддержкой zero-downtime (используется ротация индексов).

### Доступ к инструментам:
- **Prometheus**: `http://localhost:9090`
- **Grafana**: `http://localhost:3000` (Login: `admin`, Password: `admin`)
- **phpMyAdmin**: `http://localhost:8081` (Login: `root`, Password: `root`)
