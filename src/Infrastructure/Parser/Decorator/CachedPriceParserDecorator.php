<?php

namespace App\Infrastructure\Parser\Decorator;

use App\Domain\Dto\PriceDto;
use App\Domain\Service\PriceParserInterface;
use App\Infrastructure\Cache\CacheMetricsTrait;
use Prometheus\CollectorRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CachedPriceParserDecorator implements PriceParserInterface
{
    use CacheMetricsTrait;

    private const string CB_KEY = 'cb_price_parser_failures';
    private const int CB_THRESHOLD = 3;
    private const int CB_RECOVERY_SECONDS = 60;
    private const int TTL_SECONDS = 3600; // 1 hour

    public function __construct(
        private readonly PriceParserInterface $inner,
        private readonly CacheInterface $appCache,
        private readonly LoggerInterface $logger,
        private readonly CollectorRegistry $metricsRegistry
    ) {}

    public function parse(string $factory, string $collection, string $article): PriceDto
    {
        $cacheKey = sprintf('price_%s_%s_%s', md5($factory), md5($collection), md5($article));

        try {
            // Circuit breaker check
            if ($this->isCircuitOpen()) {
                $this->logger->warning('PriceParser circuit is OPEN, serving from cache if available', [
                    'factory' => $factory,
                    'collection' => $collection,
                    'article' => $article,
                ]);
                return $this->trackCache($this->appCache, $cacheKey, function (ItemInterface $item) {
                    throw new \RuntimeException('Circuit open and no cached price');
                }, 1);
            }

            // Use cache for successful responses
            return $this->trackCache($this->appCache, $cacheKey, function (ItemInterface $item) use ($factory, $collection, $article) {
                return $this->inner->parse($factory, $collection, $article);
            }, self::TTL_SECONDS);
        } catch (\Throwable $e) {
            $this->recordFailure();
            // We use warning instead of error to avoid cluttering logs on expected failures,
            // or we could check a context to see if it's a test.
            $this->logger->warning('PriceParser failed', [
                'error' => $e->getMessage(),
                'factory' => $factory,
                'collection' => $collection,
                'article' => $article,
            ]);

            // On failure, try to return stale cache if exists
            try {
                return $this->trackCache($this->appCache, $cacheKey, function (ItemInterface $item) {
                    throw new \RuntimeException('No cached value available after failure');
                }, 1);
            } catch (\Throwable) {
                throw $e;
            }
        }
    }

    private function isCircuitOpen(): bool
    {
        $failures = (int)$this->appCache->get(self::CB_KEY, function (ItemInterface $item) {
            return 0;
        });
        return $failures >= self::CB_THRESHOLD;
    }

    private function recordFailure(): void
    {
        $failures = (int)$this->appCache->get(self::CB_KEY, function (ItemInterface $item) {
            return 0;
        });
        $this->appCache->get(self::CB_KEY, function (ItemInterface $item) use ($failures) {
            $item->set($failures + 1);
            $item->expiresAfter(self::CB_RECOVERY_SECONDS);
            return $item->get();
        }, INF);
    }
}
