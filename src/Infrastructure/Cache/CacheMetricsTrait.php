<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache;

use Prometheus\CollectorRegistry;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

trait CacheMetricsTrait
{
    private function trackCache(CacheInterface $cache, string $key, callable $callback, ?int $ttl = null): mixed
    {
        $hit = true;
        $result = $cache->get($key, function (ItemInterface $item) use ($callback, $ttl, &$hit) {
            $hit = false;
            if ($ttl !== null) {
                $item->expiresAfter($ttl);
            }
            return $callback($item);
        });

        $this->incrementCacheCounter($hit ? 'redis_cache_hits_total' : 'redis_cache_misses_total');

        return $result;
    }

    private function incrementCacheCounter(string $name): void
    {
        // We use the collector registry directly if it's available as $this->metricsRegistry
        if (property_exists($this, 'metricsRegistry') && $this->metricsRegistry instanceof CollectorRegistry) {
            try {
                $this->metricsRegistry->getOrRegisterCounter(
                    'app',
                    $name,
                    str_contains($name, 'hits') ? 'Total number of redis cache hits' : 'Total number of redis cache misses',
                    []
                )->inc([]);
            } catch (\Throwable) {
                // Silently fail
            }
        }
    }
}
