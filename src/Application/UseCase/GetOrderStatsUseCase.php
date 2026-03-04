<?php

namespace App\Application\UseCase;

use App\Application\Dto\OrderStatsDto;
use App\Application\Dto\OrderStatsItemDto;
use App\Domain\Repository\OrderStatsProviderInterface;

readonly class GetOrderStatsUseCase
{
    public function __construct(
        private OrderStatsProviderInterface $statsProvider
    ) {}

    public function execute(string $groupBy, int $page, int $limit): OrderStatsDto
    {
        $statsData = $this->statsProvider->getStats($groupBy, $page, $limit);

        $items = array_map(fn (array $item) =>
            new OrderStatsItemDto(
                period: $item['period'],
                orderCount: $item['orderCount'],
                totalAmount: $item['totalAmount']
            ),
            $statsData['items']);

        $totalItems = $statsData['total'];
        $totalPages = (int) ceil($totalItems / $limit);

        return new OrderStatsDto(
            items: $items,
            totalItems: $totalItems,
            page: $page,
            limit: $limit,
            totalPages: $totalPages
        );
    }
}
