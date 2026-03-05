<?php

declare(strict_types=1);

namespace App\Infrastructure\Prometheus;

use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInitTrait;
use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInterface;
use Artprima\PrometheusMetricsBundle\Metrics\PreRequestMetricsCollectorInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class MessengerQueueCollector implements MetricsCollectorInterface, PreRequestMetricsCollectorInterface
{
    use MetricsCollectorInitTrait;

    public function __construct(
        private readonly CacheItemPoolInterface $metricsCache
    ) {
    }

    public function collectStart(RequestEvent $event): void
    {
        $gauge = $this->collectionRegistry->getOrRegisterGauge(
            $this->namespace,
            'messenger_queue_messages',
            'Number of messages in the messenger queue',
            ['queue']
        );

        $cacheKey = 'messenger_queue_count';

        try {
            $cacheItem = $this->metricsCache->getItem($cacheKey);
            $count = $cacheItem->isHit() ? (float) $cacheItem->get() : 0.0;

            $gauge->set($count, ['default']);
        } catch (\Exception) {
            // Если ошибка подключения к кешу
            $gauge->set(0, ['default']);
        }
    }
}
