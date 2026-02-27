<?php

namespace App\Controller\Api\v1;

use App\Application\Dto\OrderSearchRequestDto;
use App\Application\Dto\OrderStatsRequestDto;
use App\Application\UseCase\GetOrderStatsUseCase;
use App\Application\UseCase\GetOrderUseCase;
use App\Application\UseCase\SearchOrdersUseCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OrderController extends AbstractController
{
    #[Route('/api/v1/orders/stats', name: 'api_v1_orders_stats', methods: ['GET'])]
    public function getStats(
        Request $request,
        GetOrderStatsUseCase $useCase,
        ValidatorInterface $validator
    ): JsonResponse {
        $dto = OrderStatsRequestDto::fromRequest($request);
        $violations = $validator->validate($dto);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }

            return new JsonResponse([
                'error' => implode(', ', $errors)
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
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
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/v1/orders/search', name: 'api_v1_orders_search', methods: ['GET'])]
    public function search(
        Request $request,
        SearchOrdersUseCase $useCase,
        ValidatorInterface $validator
    ): JsonResponse {
        $dto = OrderSearchRequestDto::fromRequest($request);
        $violations = $validator->validate($dto);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }

            return new JsonResponse([
                'error' => implode(', ', $errors)
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $results = $useCase->execute($dto->query, $dto->page, $dto->limit);
            return $this->json($results);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/v1/orders/{id}', name: 'api_v1_order_get', methods: ['GET'])]
    public function getOrder(int $id, GetOrderUseCase $useCase): JsonResponse
    {
        try {
            $dto = $useCase->execute($id);
            return $this->json($dto);
        } catch (NotFoundHttpException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
}
