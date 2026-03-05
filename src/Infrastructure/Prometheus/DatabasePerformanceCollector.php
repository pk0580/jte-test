<?php

declare(strict_types=1);

namespace App\Infrastructure\Prometheus;

use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInitTrait;
use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInterface;
use Artprima\PrometheusMetricsBundle\Metrics\PreRequestMetricsCollectorInterface;
use Doctrine\DBAL\Connection;
use Prometheus\CollectorRegistry;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class DatabasePerformanceCollector implements MetricsCollectorInterface, PreRequestMetricsCollectorInterface
{
    use MetricsCollectorInitTrait;

    public function __construct(
        private readonly Connection $connection
    ) {
    }

    public function collectStart(RequestEvent $event): void
    {
        $summary = $this->collectionRegistry->getOrRegisterSummary(
            $this->namespace,
            'database_response_time_seconds',
            'Database response time in seconds',
            [],
            600,
            [0.5, 0.9, 0.99]
        );

        $start = microtime(true);
        try {
            $this->connection->executeQuery('SELECT 1');
            $duration = microtime(true) - $start;
            $summary->observe($duration);
        } catch (\Exception) {
            // Ignore connection errors during collection
        }
    }
}
