<?php

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\Order;
use App\Domain\Repository\OrderSearchIndexerRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderSearchIndexerRepository extends ServiceEntityRepository implements OrderSearchIndexerRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function findForIndexing(int $offset, int $limit): array
    {
        return $this->createQueryBuilder('o')
            ->select('o.id, o.number, o.customerInfo.email as email, o.customerInfo.name as clientName, o.customerInfo.surname as clientSurname, o.customerInfo.companyName as companyName, o.description')
            ->orderBy('o.id', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }
}
