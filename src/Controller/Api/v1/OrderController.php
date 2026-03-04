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
use Symfony\Component\Serializer\SerializerInterface;

class OrderController extends AbstractController
{
    #[Route('/api/v1/orders/stats', name: 'api_v1_orders_stats', methods: ['GET'])]
    public function getStats(
        #[MapQueryString] OrderStatsRequestDto $dto,
        GetOrderStatsUseCase $useCase,
        SerializerInterface $serializer,
    ): JsonResponse {
        $stats = $useCase->execute($dto->groupBy, $dto->page, $dto->limit);

        return new JsonResponse($serializer->serialize([
            'items' => $stats->items,
            'meta' => [
                'total_items' => $stats->totalItems,
                'page' => $stats->page,
                'limit' => $stats->limit,
                'total_pages' => $stats->totalPages,
            ]
        ], 'json'), Response::HTTP_OK, [], true);
    }

    #[Route('/api/v1/orders/search', name: 'api_v1_orders_search', methods: ['GET'])]
    public function search(
        #[MapQueryString] OrderSearchRequestDto $dto,
        SearchOrdersUseCase $useCase,
        SerializerInterface $serializer,
    ): JsonResponse {
        $results = $useCase->execute($dto->query, $dto->page, $dto->limit, $dto->lastId, $dto->status);

        return new JsonResponse($serializer->serialize($results, 'json'), Response::HTTP_OK, [], true);
    }

    #[Route('/api/v1/orders/{id}', name: 'api_v1_order_get', methods: ['GET'])]
    public function getOrder(int $id, GetOrderUseCase $useCase, SerializerInterface $serializer): JsonResponse
    {
        $dto = $useCase->execute($id);
        return new JsonResponse($serializer->serialize($dto, 'json'), Response::HTTP_OK, [], true);
    }
}
