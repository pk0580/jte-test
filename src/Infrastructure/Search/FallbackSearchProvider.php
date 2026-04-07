<?php

declare(strict_types=1);

namespace App\Infrastructure\Search;

use App\Domain\Entity\Order;
use App\Domain\Repository\OrderSearchInterface;
use App\Domain\Repository\SearchResult;
use App\Infrastructure\Monitoring\TraceIdContext;
use Psr\Log\LoggerInterface;

/**
 * Провайдер поиска с поддержкой резервного механизма (fallback).
 */
readonly class FallbackSearchProvider implements OrderSearchInterface
{
    public function __construct(
        private OrderSearchInterface $primary,
        private OrderSearchInterface $fallback,
        private LoggerInterface $logger,
        private TraceIdContext $traceIdContext
    ) {
    }

    public function search(
        string $query,
        int $page = 1,
        int $limit = 10,
        ?int $lastId = null,
        ?int $status = null
    ): SearchResult {
        try {
            $result = $this->primary->search($query, $page, $limit, $lastId, $status);
            if ($result->total > 0) {
                return $result;
            }
        } catch (\Throwable $e) {
            $this->logger->warning('Primary search failed, using fallback', [
                'error' => $e->getMessage(),
                'query' => $query,
                'trace_id' => $this->traceIdContext->getTraceId()
            ]);
        }

        return $this->fallback->search($query, $page, $limit, $lastId, $status);
    }

    public function index(Order $order): void
    {
        try {
            $this->primary->index($order);
        } catch (\Throwable $e) {
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
            $this->primary->delete($orderId);
        } catch (\Throwable $e) {
            $this->logger->error('Primary deletion failed', [
                'error' => $e->getMessage(),
                'order_id' => $orderId,
                'trace_id' => $this->traceIdContext->getTraceId()
            ]);
        }
    }

    public function ping(): bool
    {
        return $this->primary->ping();
    }
}
