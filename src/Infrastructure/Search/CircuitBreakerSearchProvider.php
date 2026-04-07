<?php

declare(strict_types=1);

namespace App\Infrastructure\Search;

use App\Domain\Entity\Order;
use App\Domain\Repository\OrderSearchInterface;
use App\Domain\Repository\SearchResult;
use App\Infrastructure\Resilience\CircuitBreaker;

/**
 * Декоратор для защиты поискового провайдера с помощью Circuit Breaker.
 */
readonly class CircuitBreakerSearchProvider implements OrderSearchInterface
{
    public function __construct(
        private OrderSearchInterface $inner,
        private CircuitBreaker $circuitBreaker
    ) {
    }

    public function search(
        string $query,
        int $page = 1,
        int $limit = 10,
        ?int $lastId = null,
        ?int $status = null
    ): SearchResult {
        return $this->circuitBreaker->call(
            fn () => $this->inner->search($query, $page, $limit, $lastId, $status)
        );
    }

    public function index(Order $order): void
    {
        $this->circuitBreaker->call(
            fn () => $this->inner->index($order)
        );
    }

    public function delete(int $orderId): void
    {
        $this->circuitBreaker->call(
            fn () => $this->inner->delete($orderId)
        );
    }

    public function ping(): bool
    {
        try {
            return $this->circuitBreaker->call(
                fn () => $this->inner->ping()
            );
        } catch (\Throwable) {
            return false;
        }
    }
}
