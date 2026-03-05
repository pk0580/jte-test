<?php

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\Order;
use App\Domain\Repository\OrderSearchIndexerRepositoryInterface;
use App\Domain\Dto\Search\SearchOrderDto;
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
        $data = $this->createQueryBuilder('o')
            ->select(sprintf(
                'NEW %s(o.id, o.number, o.customerInfo.email, o.customerInfo.name, o.customerInfo.surname, o.customerInfo.companyName, o.description, o.status)',
                SearchOrderDto::class
            ))
            ->orderBy('o.id', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return array_map(fn(SearchOrderDto $dto) => (array)$dto, $data);
    }

    public function getIterableForIndexing(): iterable
    {
        $query = $this->createQueryBuilder('o')
            ->select(sprintf(
                'NEW %s(o.id, o.number, o.customerInfo.email, o.customerInfo.name, o.customerInfo.surname, o.customerInfo.companyName, o.description, o.status)',
                SearchOrderDto::class
            ))
            ->orderBy('o.id', 'ASC')
            ->getQuery();

        foreach ($query->toIterable() as $dto) {
            yield (array)$dto;
        }
    }
}
