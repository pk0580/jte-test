<?php

namespace App\Infrastructure\Search;

use App\Domain\Dto\Search\SearchOrderDto;
use App\Domain\Repository\OrderSearchInterface;
use App\Domain\Repository\SearchResult;
use App\Domain\Entity\Order;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

readonly class CombinedSearchProvider implements OrderSearchInterface
{
    private const string CB_KEY = 'circuit_breaker_manticore';

    public function __construct(
        private OrderSearchInterface $primarySearch,
        private OrderSearchInterface $fallbackSearch,
        private LoggerInterface      $logger,
        private CacheInterface       $appCache,
        private int                  $failureThreshold = 3,
        private int                  $recoveryTime = 60
    ) {}

    /**
     * @param string $query
     * @param int $page
     * @param int $limit
     * @param int|null $lastId
     * @param int|null $status
     * @return SearchResult<SearchOrderDto>
     */
    public function search(
        string $query,
        int $page = 1,
        int $limit = 10,
        ?int $lastId = null,
        ?int $status = null
    ): SearchResult
    {
        $startTime = microtime(true);
        try {
            if ($this->isCircuitOpen()) {
                $this->logger->warning('Circuit is OPEN, using fallback search', ['query' => $query]);
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
        } finally {
            $duration = (microtime(true) - $startTime) * 1000;
            if ($duration > 500) { // Log slow searches > 500ms
                $this->logger->warning('Search is slow', [
                    'duration_ms' => $duration,
                    'query' => $query,
                    'page' => $page
                ]);
            }
        }
    }

    private function isCircuitOpen(): bool
    {
        $failures = (int)$this->appCache->get(self::CB_KEY, fn() => 0);
        return $failures >= $this->failureThreshold;
    }

    private function recordFailure(): void
    {
        $failures = (int)$this->appCache->get(self::CB_KEY, fn() => 0);

        $this->appCache->get(self::CB_KEY, function (ItemInterface $item) use ($failures) {
            $item->set($failures + 1);
            $item->expiresAfter($this->recoveryTime);
            return $item->get();
        }, INF); // Force save
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
