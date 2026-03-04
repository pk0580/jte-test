<?php

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\PayType;
use App\Domain\Repository\PayTypeRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @extends ServiceEntityRepository<PayType>
 */
class PayTypeRepository extends ServiceEntityRepository implements PayTypeRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly CacheInterface $referenceCache
    ) {
        parent::__construct($registry, PayType::class);
    }

    public function findById(int $id): ?PayType
    {
        return $this->referenceCache->get(sprintf('pay_type_%d', $id), function (ItemInterface $item) use ($id) {
            $item->expiresAfter(3600);
            return $this->find($id);
        });
    }
}
