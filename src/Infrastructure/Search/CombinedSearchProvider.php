<?php

namespace App\Infrastructure\Search;

use App\Domain\Repository\OrderSearchInterface;
use App\Domain\Repository\SearchResult;
use App\Domain\Entity\Order;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

readonly class CombinedSearchProvider implements OrderSearchInterface
{
    private const string CB_KEY = 'circuit_breaker_manticore';
    private const int CB_FAILURE_THRESHOLD = 3;
    private const int CB_RECOVERY_TIME = 60; // seconds

    public function __construct(
        private OrderSearchInterface $primarySearch,
        private OrderSearchInterface $fallbackSearch,
        private LoggerInterface      $logger,
        private CacheInterface       $appCache
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
        if ($this->isCircuitOpen()) {
            return $this->fallbackSearch->search($query, $page, $limit, $lastId, $status);
        }

        try {
            $result = $this->primarySearch->search($query, $page, $limit, $lastId, $status);
            $this->resetFailures();
            return $result;
        } catch (\Exception $e) {
            $this->recordFailure();
            $this->logger->error('Primary search failed, falling back', [
                'error' => $e->getMessage(),
                'query' => $query
            ]);
        }

        return $this->fallbackSearch->search($query, $page, $limit, $lastId, $status);
    }

    private function isCircuitOpen(): bool
    {
        $failures = (int)$this->appCache->get(self::CB_KEY, fn() => 0);
        return $failures >= self::CB_FAILURE_THRESHOLD;
    }

    private function recordFailure(): void
    {
        $this->appCache->get(self::CB_KEY, function (ItemInterface $item) {
            $currentValue = (int)$item->get();
            $item->set($currentValue + 1);
            $item->expiresAfter(self::CB_RECOVERY_TIME);
            return $item->get();
        });
    }

    private function resetFailures(): void
    {
        $this->appCache->delete(self::CB_KEY);
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
