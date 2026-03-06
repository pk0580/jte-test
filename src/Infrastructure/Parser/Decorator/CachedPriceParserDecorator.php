<?php

namespace App\Infrastructure\Parser\Decorator;

use App\Domain\Dto\PriceDto;
use App\Domain\Service\PriceParserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CachedPriceParserDecorator implements PriceParserInterface
{
    private const string CB_KEY = 'cb_price_parser_failures';
    private const int CB_THRESHOLD = 3;
    private const int CB_RECOVERY_SECONDS = 60;
    private const int TTL_SECONDS = 3600; // 1 hour

    public function __construct(
        private readonly PriceParserInterface $inner,
        private readonly CacheInterface $appCache,
        private readonly LoggerInterface $logger
    ) {}

    public function parse(string $factory, string $collection, string $article): PriceDto
    {
        $cacheKey = sprintf('price_%s_%s_%s', md5($factory), md5($collection), md5($article));

        // Circuit breaker check
        if ($this->isCircuitOpen()) {
            $this->logger->warning('PriceParser circuit is OPEN, serving from cache if available', [
                'factory' => $factory,
                'collection' => $collection,
                'article' => $article,
            ]);
            $cached = $this->appCache->get($cacheKey, function (ItemInterface $item) {
                // Don't cache miss here; just immediate failure
                $item->expiresAfter(1);
                throw new \RuntimeException('Circuit open and no cached price');
            });
            return $cached;
        }

        try {
            // Use cache for successful responses
            return $this->appCache->get($cacheKey, function (ItemInterface $item) use ($factory, $collection, $article) {
                $item->expiresAfter(self::TTL_SECONDS);
                return $this->inner->parse($factory, $collection, $article);
            });
        } catch (\Throwable $e) {
            $this->recordFailure();
            $this->logger->error('PriceParser failed', [
                'error' => $e->getMessage(),
                'factory' => $factory,
                'collection' => $collection,
                'article' => $article,
            ]);

            // On failure, try to return stale cache if exists
            return $this->appCache->get($cacheKey, function (ItemInterface $item) {
                $item->expiresAfter(1);
                throw new \RuntimeException('No cached value available after failure');
            });
        }
    }

    private function isCircuitOpen(): bool
    {
        $failures = (int)$this->appCache->get(self::CB_KEY, fn() => 0);
        return $failures >= self::CB_THRESHOLD;
    }

    private function recordFailure(): void
    {
        $failures = (int)$this->appCache->get(self::CB_KEY, fn() => 0);
        $this->appCache->get(self::CB_KEY, function (ItemInterface $item) use ($failures) {
            $item->set($failures + 1);
            $item->expiresAfter(self::CB_RECOVERY_SECONDS);
            return $item->get();
        }, INF);
    }
}
