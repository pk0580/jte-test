<?php

declare(strict_types=1);

namespace App\Infrastructure\Service\Metrics;

use App\Domain\Service\Metrics\MetricsServiceInterface;
use Prometheus\CollectorRegistry;

readonly class PrometheusMetricsService implements MetricsServiceInterface
{
    public function __construct(
        private CollectorRegistry $collectorRegistry
    ) {
    }

    public function incrementOrdersCreated(array $labels = []): void
    {
        try {
            $counter = $this->collectorRegistry->getOrRegisterCounter(
                'app',
                'orders_created_total',
                'Total number of orders created',
                array_keys($labels)
            );
            $counter->inc(array_values($labels));
        } catch (\Exception) {
            // Метрики не должны ломать основной процесс
        }
    }
}
