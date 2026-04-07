# План создания Symfony приложения

Данный документ описывает этапы разработки приложения согласно техническому заданию.

## 1. Подготовка инфраструктуры (Docker)
- Настройка `docker-compose.yml`:
    - PHP 8.4+ (FPM).
    - Nginx (веб-сервер).
    - MySQL (база данных на основе `dump.sql`).
    - Manticore Search (для полнотекстового поиска).
- Вынос настроек портов (Nginx, MySQL) в `.env` файл.

## 2. Инициализация Symfony проекта
- Создание нового проекта Symfony Skeleton.
- Установка необходимых пакетов:
    - `maker-bundle` (для генерации кода).
    - `orm-pack` (Doctrine).
    - `serializer-pack`.
    - `api-platform/core` или `nelmio/api-doc-bundle` (для Swagger/OpenAPI).
    - `symfony/http-client` (для парсинга эндпоинта №1).
    - `symfony/dom-crawler` (для извлечения данных со страницы).
    - `besimple/soap-bundle` или стандартный PHP `SoapServer` (для SOAP).
    - `manticoresoftware/manticoresearch-php` (для поиска).

## 3. Проектирование и моделирование (Clean Architecture)
- Создание сущностей Doctrine для `Order` и `OrderArticle` (Domain Layer).
- Настройка связей One-To-Many между заказом и его позициями.
- Определение интерфейсов репозиториев в Domain Layer.
- Создание DTO для входных данных и ответов API.

## 4. Реализация Эндпоинта №1 (Парсинг цены) [DONE]
- Маршрут: `GET /api/v1/price`.
- Архитектура:
    - `PriceParserInterface` в доменном слое.
    - Реализация парсера в инфраструктурном слое (использование `HttpClient`, `DomCrawler`).
    - Application Service для координации процесса.
- Логика:
    - Формирование URL на основе параметров `factory`, `collection`, `article`.
    - Извлечение цены и возврат через DTO.
- Ответ: JSON согласно формату в ТЗ.

## 5. Реализация Эндпоинта №2 (Группировка заказов) [DONE]
- Маршрут: `GET /api/v1/orders/stats`.
- Архитектура:
    - Use Case / Application Service для получения статистики. [DONE]
    - Repository Method для агрегации данных. [DONE]
- Параметры: `page`, `limit`, `group_by` (day, month, year).
- Логика:
    - Использование SQL/QueryBuilder для агрегации данных по датам. [DONE]
    - Реализация пагинации. [DONE]
- Ответ: JSON с метаданными пагинации и списком группировок (через DTO). [DONE]

## 6. Реализация Эндпоинта №3 (SOAP-создание заказа) [DONE]
- WSDL описание сервиса. [DONE]
- Архитектура:
    - SoapController как тонкий входной узел. [DONE]
    - Use Case для создания заказа. [DONE]
    - Валидация данных заказа в Domain/Application слое. [DONE]
- Логика сохранения данных о заказе в базу через репозиторий. [DONE]
- Исправление ошибок SoapServer (обработка буфера, разделение DTO для автозагрузки). [DONE]

## 7. Реализация Эндпоинта №4 (Получение заказа) [DONE]
- Маршрут: `GET /api/v1/orders/{id}`. [DONE]
- Архитектура: Query Service или Repository для получения заказа и преобразование в DTO. [DONE]
- Логика: Получение заказа со всеми связанными товарами (`OrderArticle`). [DONE]

## 8. Поиск через Manticore Search [DONE]
- Настройка индекса для заказов. [DONE]
- Реализация `OrderSearchInterface` (Domain) и его реализации для Manticore (Infrastructure). [DONE]
- Реализация сервиса синхронизации данных (Event Listener на изменения сущностей). [DONE]
- Эндпоинт `GET /api/v1/orders/search` для полнотекстового поиска. [DONE]
    - Добавлена валидация параметров (`query`, `page`, `limit`) через DTO. [DONE]
- Консольная команда `app:index-orders` для первичной переиндексации. [DONE]
    - Реализована production-ready версия для 1M+ записей. [DONE]
    - Использование `HYDRATE_ARRAY` для исключения гидратации сущностей и N+1. [DONE]
    - Постраничная выборка и `$entityManager->clear()` для предотвращения утечек памяти. [DONE]
    - Использование низкоуровневого Bulk API Manticore для максимальной производительности (multi-value REPLACE INTO). [DONE]
    - Устранена проблема N+1 в поиске через `findByIds` с JOIN-загрузкой позиций. [DONE]
    - Оптимизирована ротация индексов (атомарная замена через multi-table ALTER TABLE RENAME). [DONE]
    - Добавлен троттлинг (throttling) для предотвращения перегрузки поискового движка. [DONE]
    - Реализовано ограничение размера SQL-запроса (max_packet_size protection) при bulk-индексации: батчи автоматически разбиваются на части по 4МБ. [DONE]
    - Улучшена безопасность (экранирование) SQL-запросов к Manticore. [DONE]
    - Проверена и исправлена работа с SQL-клиентом Manticore (обработка ответов в raw режиме). [DONE]
    - Подтверждена работоспособность zero-downtime переиндексации. [DONE]

## 9. Документация и Тестирование [DONE]
- Настройка NelmioApiDocBundle. [DONE]
    - Реализована документация в формате JSON (OpenAPI 3.0) по адресу `/api/doc.json`. [DONE]
    - Реализован Swagger UI (HTML) по адресу `/api/doc`. [DONE]
    - Установлен `symfony/twig-bundle` для работы UI. [DONE]
- Написание README.md с описанием всех эндпоинтов и примерами запросов. [DONE]
- Покрытие тестами: [DONE]
    - Unit-тесты для Domain Logic и Application Services. [DONE]
    - Integration-тесты для Infrastructure (Manticore, DB). [DONE]
    - Functional-тесты для API эндпоинтов. [DONE]
