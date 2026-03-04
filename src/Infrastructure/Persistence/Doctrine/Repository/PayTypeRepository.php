<?php

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\PayType;
use App\Domain\Repository\PayTypeRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PayType>
 */
class PayTypeRepository extends ServiceEntityRepository implements PayTypeRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry
    ) {
        parent::__construct($registry, PayType::class);
    }

    public function findById(int $id): ?PayType
    {
        return $this->find($id);
    }
}
