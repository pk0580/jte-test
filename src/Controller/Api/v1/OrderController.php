<?php

namespace App\Controller\Api\v1;

use App\Application\Dto\Search\OrderSearchRequestDto;
use App\Application\Dto\OrderStatsRequestDto;
use App\Application\UseCase\GetOrderStatsUseCase;
use App\Application\UseCase\GetOrderUseCase;
use App\Application\UseCase\SearchOrdersUseCase;
use App\Domain\Repository\OrderRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class OrderController extends AbstractController
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly CacheInterface $appCache
    ) {}

    private function getLastUpdateTimestamp(): string
    {
        return (string)$this->appCache->get('order_last_update_timestamp', function () {
            return (string)($this->orderRepository->getLastUpdateTimestamp() ?? time());
        });
    }

    #[Route('/api/v1/orders/stats', name: 'api_v1_orders_stats', methods: ['GET'])]
    public function getStats(
        #[MapQueryString] OrderStatsRequestDto $dto,
        GetOrderStatsUseCase $useCase,
        SerializerInterface $serializer,
        Request $request,
    ): Response {
        $lastUpdate = $this->getLastUpdateTimestamp();
        $response = new Response();
        $response->setEtag(md5(($request->getQueryString() ?? '') . $lastUpdate));
        $response->setPublic();

        if ($response->isNotModified($request)) {
            return $response;
        }

        $stats = $useCase->execute($dto->groupBy, $dto->page, $dto->limit);

        $response->setContent($serializer->serialize([
            'items' => $stats->items,
            'meta' => [
                'total_items' => $stats->totalItems,
                'page' => $stats->page,
                'limit' => $stats->limit,
                'total_pages' => $stats->totalPages,
            ]
        ], 'json'));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    #[Route('/api/v1/orders/search', name: 'api_v1_orders_search', methods: ['GET'])]
    public function search(
        #[MapQueryString] OrderSearchRequestDto $dto,
        SearchOrdersUseCase $useCase,
        SerializerInterface $serializer,
        Request $request,
    ): Response {
        $lastUpdate = $this->getLastUpdateTimestamp();
        $response = new Response();
        $response->setEtag(md5(($request->getQueryString() ?? '') . $lastUpdate));
        $response->setPublic();

        if ($response->isNotModified($request)) {
            return $response;
        }

        $results = $useCase->execute($dto->query, $dto->page, $dto->limit, $dto->lastId, $dto->status);

        $response->setContent($serializer->serialize($results, 'json'));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    #[Route('/api/v1/orders/{id}', name: 'api_v1_order_get', methods: ['GET'])]
    public function getOrder(int $id, GetOrderUseCase $useCase, SerializerInterface $serializer, Request $request): Response
    {
        $dto = $useCase->execute($id);

        $response = new Response();
        $response->setEtag(md5($dto->id . $dto->createDate));
        $response->setLastModified(new \DateTime($dto->createDate));
        $response->setPublic();

        if ($response->isNotModified($request)) {
            return $response;
        }

        $response->setContent($serializer->serialize($dto, 'json'));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
