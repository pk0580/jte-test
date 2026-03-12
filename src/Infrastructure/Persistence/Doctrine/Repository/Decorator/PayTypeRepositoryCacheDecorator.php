<?php

namespace App\Infrastructure\Persistence\Doctrine\Repository\Decorator;

use App\Domain\Entity\PayType;
use App\Domain\Repository\PayTypeRepositoryInterface;
use App\Infrastructure\Cache\CacheMetricsTrait;
use Prometheus\CollectorRegistry;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class PayTypeRepositoryCacheDecorator implements PayTypeRepositoryInterface
{
    use CacheMetricsTrait;

    public function __construct(
        private readonly PayTypeRepositoryInterface $inner,
        private readonly CacheInterface $referenceCache,
        private readonly CollectorRegistry $metricsRegistry
    ) {}

    public function findById(int $id): ?PayType
    {
        return $this->trackCache($this->referenceCache, sprintf('pay_type_%d', $id), function (ItemInterface $item) use ($id) {
            return $this->inner->findById($id);
        }, 3600);
    }
}
