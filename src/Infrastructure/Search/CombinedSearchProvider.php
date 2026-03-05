<?php

namespace App\Infrastructure\Search;

use App\Domain\Dto\Search\SearchOrderDto;
use App\Domain\Repository\OrderSearchInterface;
use App\Domain\Repository\SearchResult;
use App\Domain\Entity\Order;
use App\Infrastructure\Monitoring\TraceIdContext;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

readonly class CombinedSearchProvider implements OrderSearchInterface
{
    private const string CB_KEY = 'circuit_breaker_manticore';
    private CacheInterface $localCache;

    public function __construct(
        private OrderSearchInterface $primarySearch,
        private OrderSearchInterface $fallbackSearch,
        private LoggerInterface      $logger,
        private CacheInterface       $appCache,
        private TraceIdContext       $traceIdContext,
        private int                  $failureThreshold = 3,
        private int                  $recoveryTime = 60
    ) {
        $this->localCache = extension_loaded('apcu') && ini_get('apc.enabled')
            ? new ApcuAdapter('cb_', 0)
            : $this->appCache;
    }

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
                $this->logger->warning('Circuit is OPEN, using fallback search', [
                    'query' => $query,
                    'trace_id' => $this->traceIdContext->getTraceId()
                ]);
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
                    'query' => $query,
                    'trace_id' => $this->traceIdContext->getTraceId()
                ]);
            }

            return $this->fallbackSearch->search($query, $page, $limit, $lastId, $status);
        } finally {
            $duration = (microtime(true) - $startTime) * 1000;
            if ($duration > 500) { // Log slow searches > 500ms
                $this->logger->warning('Search is slow', [
                    'duration_ms' => $duration,
                    'query' => $query,
                    'page' => $page,
                    'trace_id' => $this->traceIdContext->getTraceId()
                ]);
            }
        }
    }

    private function isCircuitOpen(): bool
    {
        $failures = (int)$this->localCache->get(self::CB_KEY, fn() => 0);
        return $failures >= $this->failureThreshold;
    }

    private function recordFailure(): void
    {
        $item = $this->localCache->getItem(self::CB_KEY);
        $count = $item->isHit() ? (int)$item->get() + 1 : 1;

        $item->set($count);
        $item->expiresAfter($this->recoveryTime);
        $this->localCache->save($item);

        if ($this->localCache !== $this->appCache) {
             $appItem = $this->appCache->getItem(self::CB_KEY);
             $appCount = $appItem->isHit() ? (int)$appItem->get() + 1 : 1;
             $appItem->set($appCount);
             $appItem->expiresAfter($this->recoveryTime);
             $this->appCache->save($appItem);
        }
    }

    private function resetFailures(): void
    {
        if (ApcuAdapter::isSupported()) {
            $this->localCache->delete(self::CB_KEY);
        }
        $this->appCache->delete(self::CB_KEY);
    }

    public function index(Order $order): void
    {
        try {
            if ($this->isCircuitOpen()) {
                return;
            }
            $this->primarySearch->index($order);
            $this->resetFailures();
        } catch (\Exception $e) {
            $this->recordFailure();
            $this->logger->error('Primary indexing failed', [
                'error' => $e->getMessage(),
                'order_id' => $order->getId(),
                'trace_id' => $this->traceIdContext->getTraceId()
            ]);
        }
    }

    public function delete(int $orderId): void
    {
        try {
            if ($this->isCircuitOpen()) {
                return;
            }
            $this->primarySearch->delete($orderId);
            $this->resetFailures();
        } catch (\Exception $e) {
            $this->recordFailure();
            $this->logger->error('Primary deletion failed', [
                'error' => $e->getMessage(),
                'order_id' => $orderId,
                'trace_id' => $this->traceIdContext->getTraceId()
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
