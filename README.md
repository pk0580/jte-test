# JTE Test App (Symfony API)

Приложение на Symfony 8.0 для управления заказами и проверки цен.

## Технологический стек
- **PHP 8.4** (FPM)
- **Symfony 8.0** (Skeleton)
- **MySQL 8.0** (База данных)
- **Manticore Search 6.2** (Полнотекстовый поиск)
- **Docker & Docker Compose** (Инфраструктура)

---

## Установка и запуск

### 1. Клонирование репозитория и настройка окружения
```bash
cp .env .env.local
```
Отредактируйте `.env.local`, если необходимо изменить порты или доступы к БД.

### 2. Запуск контейнеров
```bash
docker-compose up -d
```

### 3. Установка зависимостей и подготовка БД
```bash
docker-compose exec php composer install
docker-compose exec php bin/console doctrine:migrations:migrate --no-interaction
```

### 4. Первичная индексация Manticore Search
Для работы полнотекстового поиска необходимо проиндексировать данные:
```bash
docker-compose exec php bin/console app:index-orders
```

---

## API Эндпоинты

### Документация (OpenAPI)
Документация доступна в двух форматах:
- **Swagger UI (HTML)**: `GET /api/doc`
- **OpenAPI JSON**: `GET /api/doc.json`

Вы можете открыть [http://localhost:8080/api/doc](http://localhost:8080/api/doc) в браузере для просмотра интерактивной документации.

### 1. Получение цены (Парсинг)
**Запрос:** `GET /api/v1/price?factory=...&collection=...&article=...`

Пример:
```bash
curl "http://localhost:8080/api/v1/price?factory=A&collection=B&article=C"
```

### 2. Статистика по заказам
**Запрос:** `GET /api/v1/orders/stats?group_by=day&page=1&limit=10`

Параметры `group_by`: `day`, `month`, `year`.

### 3. Создание заказа (SOAP)
**WSDL:** `http://localhost:8080/api/v1/soap?wsdl`

Эндпоинт принимает SOAP-запросы для создания заказов.

### 4. Получение заказа по ID
**Запрос:** `GET /api/v1/orders/{id}`

### 5. Поиск заказов (Manticore)
**Запрос:** `GET /api/v1/orders/search?query=...&page=1&limit=10`

---

## Тестирование
Запуск всех тестов:
```bash
docker-compose exec php vendor/bin/phpunit
```

## Архитектура
Проект реализован с использованием принципов **Clean Architecture**:
- `src/Domain`: Сущности и интерфейсы репозиториев.
- `src/Application`: Use Cases и DTO.
- `src/Infrastructure`: Реализация интерфейсов (Doctrine, Manticore, External Parsers), контроллеры.

## Команды Manticore
- `app:index-orders` — Полная переиндексация заказов из MySQL в Manticore Search. Поддерживает zero-downtime через ротацию индексов.

---
