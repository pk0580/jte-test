<?php

declare(strict_types=1);

namespace App\Infrastructure\Prometheus;

use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInitTrait;
use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInterface;
use Artprima\PrometheusMetricsBundle\Metrics\PreRequestMetricsCollectorInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class DoctrineFlushCollector implements MetricsCollectorInterface, PreRequestMetricsCollectorInterface
{
    use MetricsCollectorInitTrait;

    public function __construct(
        private readonly CacheInterface $appCache
    ) {
    }

    public function collectStart(RequestEvent $event): void
    {
        $summary = $this->collectionRegistry->getOrRegisterSummary(
            $this->namespace,
            'doctrine_flush_duration_seconds',
            'Duration of Doctrine flush process',
            [],
            600,
            [0.5, 0.9, 0.99]
        );

        try {
            $durations = $this->appCache->get('doctrine_flush_duration', function (ItemInterface $item) {
                return [];
            });
            // @phpstan-ignore-next-line
            if (is_array($durations)) {
                // @phpstan-ignore-next-line
                foreach ($durations as $duration) {
                    $summary->observe((float) $duration, []);
                }
            }
        } catch (\Exception) {
            // Ignore cache errors during collection
        }
    }
}
