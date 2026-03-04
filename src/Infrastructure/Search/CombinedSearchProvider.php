<?php

namespace App\Infrastructure\Search;

use App\Domain\Repository\OrderSearchInterface;
use App\Domain\Repository\SearchResult;
use App\Domain\Entity\Order;
use Psr\Log\LoggerInterface;

readonly class CombinedSearchProvider implements OrderSearchInterface
{
    public function __construct(
        private OrderSearchInterface $primarySearch,
        private OrderSearchInterface $fallbackSearch,
        private LoggerInterface      $logger
    ) {}

    /**
     * @param string $query
     * @param int $page
     * @param int $limit
     * @param int|null $lastId
     * @param int|null $status
     * @return SearchResult<Order>
     */
    public function search(
        string $query,
        int $page = 1,
        int $limit = 10,
        ?int $lastId = null,
        ?int $status = null
    ): SearchResult
    {
        try {
            return $this->primarySearch->search($query, $page, $limit, $lastId, $status);
        } catch (\Exception $e) {
            $this->logger->error('Primary search failed, falling back', [
                'error' => $e->getMessage(),
                'query' => $query
            ]);
        }

        return $this->fallbackSearch->search($query, $page, $limit, $lastId, $status);
    }

    public function index(Order $order): void
    {
        try {
            $this->primarySearch->index($order);
        } catch (\Exception $e) {
            $this->logger->error('Primary indexing failed', [
                'error' => $e->getMessage(),
                'order_id' => $order->getId()
            ]);
        }
    }

    public function delete(int $orderId): void
    {
        try {
            $this->primarySearch->delete($orderId);
        } catch (\Exception $e) {
            $this->logger->error('Primary deletion failed', [
                'error' => $e->getMessage(),
                'order_id' => $orderId
            ]);
        }
    }

    public function ping(): bool
    {
        try {
            return $this->primarySearch->ping();
        } catch (\Exception $e) {
            $this->logger->error('Ping failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
