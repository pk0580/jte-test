<?php

namespace App\Controller\Api\v1;

use App\Application\Dto\Search\OrderSearchRequestDto;
use App\Application\Dto\OrderStatsRequestDto;
use App\Application\UseCase\GetOrderStatsUseCase;
use App\Application\UseCase\GetOrderUseCase;
use App\Application\UseCase\SearchOrdersUseCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class OrderController extends AbstractController
{
    #[Route('/api/v1/orders/stats', name: 'api_v1_orders_stats', methods: ['GET'])]
    public function getStats(
        #[MapQueryString] OrderStatsRequestDto $dto,
        GetOrderStatsUseCase $useCase,
    ): JsonResponse {
        $stats = $useCase->execute($dto->groupBy, $dto->page, $dto->limit);

        return new JsonResponse([
            'items' => array_map(fn($item) => [
                'period' => $item->period,
                'order_count' => $item->orderCount,
                'total_amount' => $item->totalAmount,
            ], $stats->items),
            'meta' => [
                'total_items' => $stats->totalItems,
                'page' => $stats->page,
                'limit' => $stats->limit,
                'total_pages' => $stats->totalPages,
            ]
        ]);
    }

    #[Route('/api/v1/orders/search', name: 'api_v1_orders_search', methods: ['GET'])]
    public function search(
        #[MapQueryString] OrderSearchRequestDto $dto,
        SearchOrdersUseCase $useCase,
    ): JsonResponse {
        $results = $useCase->execute($dto->query, $dto->page, $dto->limit, $dto->lastId, $dto->status);
        return $this->json([
            'items' => $results->items,
            'meta' => [
                'total' => $results->total,
                'page' => $dto->page,
                'limit' => $dto->limit,
                'last_id' => $dto->lastId,
                'status' => $dto->status,
            ]
        ]);
    }

    #[Route('/api/v1/orders/{id}', name: 'api_v1_order_get', methods: ['GET'])]
    public function getOrder(int $id, GetOrderUseCase $useCase): JsonResponse
    {
        $dto = $useCase->execute($id);
        return $this->json($dto);
    }
}
