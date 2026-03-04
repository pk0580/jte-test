<?php

namespace App\Infrastructure\Persistence\Doctrine\Repository\Decorator;

use App\Domain\Entity\PayType;
use App\Domain\Repository\PayTypeRepositoryInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class PayTypeRepositoryCacheDecorator implements PayTypeRepositoryInterface
{
    public function __construct(
        private readonly PayTypeRepositoryInterface $inner,
        private readonly CacheInterface $referenceCache
    ) {}

    public function findById(int $id): ?PayType
    {
        return $this->referenceCache->get(sprintf('pay_type_%d', $id), function (ItemInterface $item) use ($id) {
            $item->expiresAfter(3600);
            return $this->inner->findById($id);
        });
    }
}
