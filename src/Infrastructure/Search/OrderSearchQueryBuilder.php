<?php

declare(strict_types=1);

namespace App\Infrastructure\Search;

use App\Infrastructure\Search\Dto\SearchQueryDto;

class OrderSearchQueryBuilder
{
    /**
     * Формирует DTO поискового запроса на основе переданных параметров.
     *
     * @param string $query Строка поиска
     * @param int $page Номер страницы
     * @param int $limit Количество результатов на странице
     * @param int|null $lastId ID последнего элемента для курсорной пагинации
     * @param int|null $status Фильтр по статусу
     * @return SearchQueryDto
     */
    public function build(string $query, int $page, int $limit, ?int $lastId = null, ?int $status = null): SearchQueryDto
    {
        $isCursorPagination = $lastId !== null && $lastId > 0;

        if ($isCursorPagination) {
            $offset = 0;
            // Для курсорной пагинации всегда нужна предсказуемая сортировка
            $sort = ['id' => 'desc'];
        } else {
            // Оптимизация при высокой нагрузке: ограничиваем максимальный OFFSET для предотвращения деградации производительности
            // Если номер страницы слишком велик, следует рекомендовать UI использовать курсорную пагинацию
            $maxOffset = 10000;
            $offset = ($page - 1) * $limit;

            if ($offset > $maxOffset) {
                $offset = $maxOffset;
            }

            $sort = [];
        }

        // Применяем веса для совпадений
        $weightedQuery = "@number ^5 | @email ^3 | @client_name ^2 | @client_surname ^2 | @company_name ^1 | $query";
        if (str_contains($query, '|') || str_contains($query, '@') || str_contains($query, '^')) {
            // Если пользователь уже использует специальный синтаксис, не перезаписываем его
            $weightedQuery = $query;
        }

        return new SearchQueryDto(
            query: $weightedQuery,
            originalQuery: $query,
            offset: $offset,
            limit: $limit,
            lastId: $lastId,
            sort: $sort,
            status: $status
        );
    }
}
