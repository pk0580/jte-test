<?php

namespace App\Infrastructure\Persistence\Doctrine\Repository\Decorator;

use App\Domain\Entity\Article;
use App\Domain\Repository\ArticleRepositoryInterface;
use App\Infrastructure\Cache\CacheMetricsTrait;
use Prometheus\CollectorRegistry;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class ArticleRepositoryCacheDecorator implements ArticleRepositoryInterface
{
    use CacheMetricsTrait;

    public function __construct(
        private readonly ArticleRepositoryInterface $inner,
        private readonly CacheInterface $referenceCache,
        private readonly CollectorRegistry $metricsRegistry
    ) {}

    public function findById(int $id): ?Article
    {
        return $this->trackCache($this->referenceCache, sprintf('article_%d', $id), function (ItemInterface $item) use ($id) {
            return $this->inner->findById($id);
        }, 3600);
    }
}
