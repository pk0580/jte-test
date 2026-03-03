# Настройка Swagger (OpenAPI)

В проекте настроена автоматическая генерация документации API с использованием бандла `NelmioApiDocBundle`. Ниже приведено подробное описание конфигурации.

## 1. Установка зависимостей
Для работы Swagger были установлены следующие пакеты:
*   `nelmio/api-doc-bundle`: Основной бандл для интеграции OpenAPI/Swagger в Symfony.
*   `twig-bundle` и `asset`: Необходимы для визуализации интерфейса Swagger UI.
*   `phpdocumentor/reflection-docblock` и `phpstan/phpdoc-parser`: Для анализа PHP-аннотаций и типов данных.

## 2. Адреса получения документации
Маршруты для доступа к документации настроены в файле `config/routes/nelmio_api_doc.yaml`:

*   **Swagger UI (Интерактивный интерфейс):** `GET /api/doc`
    *   URL: [http://localhost:8080/api/doc](http://localhost:8080/api/doc)
*   **OpenAPI JSON (Спецификация):** `GET /api/doc.json`
    *   URL: [http://localhost:8080/api/doc.json](http://localhost:8080/api/doc.json)

## 3. Конфигурация бандла
Основная конфигурация находится в `config/packages/nelmio_api_doc.yaml`.

### Области сканирования (Areas)
Настроена область `default`, которая включает все пути, начинающиеся с `/api`, за исключением самого пути документации:
```yaml
nelmio_api_doc:
    areas:
        default:
            path_patterns:
                - ^/api(?!/doc$)
```

### Информация об API
В секции `documentation.info` указаны:
*   **Заголовок:** JTE Test App API
*   **Описание:** API for managing orders and checking prices.
*   **Версия:** 1.0.0

### Описание эндпоинтов
Для обеспечения точности документации, параметры и описания для ключевых эндпоинтов прописаны в YAML-конфигурации:
*   `/api/v1/price`: Получение цены (query-параметры `factory`, `collection`, `article`).
*   `/api/v1/orders/stats`: Статистика заказов (параметры `group_by`, `page`, `limit`).
*   `/api/v1/orders/search`: Поиск заказов (Manticore Search).
*   `/api/v1/orders/{id}`: Получение заказа по ID.

## 4. Интеграция с кодом
Бандл автоматически дополняет документацию, анализируя:
1.  Атрибуты Symfony (например, `#[Route]`).
2.  PHP-типы параметров и возвращаемых значений в контроллерах.
3.  Аннотации PHP DocBlock.
