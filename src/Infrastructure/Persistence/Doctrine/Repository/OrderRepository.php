<?php

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\Order;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Infrastructure\Search\OrderSearchQueryBuilder;
use App\Domain\Repository\OrderSearchInterface;
use App\Domain\Repository\SearchResult;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository implements OrderRepositoryInterface, OrderSearchInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly OrderSearchQueryBuilder $queryBuilder
    ) {
        parent::__construct($registry, Order::class);
    }

    public function findById(int $id): ?Order
    {
        return $this->find($id);
    }

    public function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return $this->createQueryBuilder('o')
            ->select('o', 'a')
            ->leftJoin('o.articles', 'a')
            ->where('o.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    public function save(Order $order): void
    {
        $em = $this->getEntityManager();
        $em->persist($order);
        $em->flush();
    }

    public function remove(Order $order): void
    {
        $em = $this->getEntityManager();
        $em->remove($order);
        $em->flush();
    }

    public function getStats(string $groupBy, int $page, int $limit): array
    {
        $dateFormat = match ($groupBy) {
            'year' => '%Y',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $conn = $this->getEntityManager()->getConnection();

        $offset = ($page - 1) * $limit;

        $sql = "
            SELECT
                DATE_FORMAT(o.create_date, :format) as period,
                COUNT(*) as orderCount,
                SUM(o.total_amount) as totalAmount
            FROM `orders` o
            GROUP BY period
            ORDER BY period DESC
            LIMIT :limit
            OFFSET :offset
        ";

        $items = $conn->fetchAllAssociative($sql, [
            'format' => $dateFormat,
            'limit' => $limit,
            'offset' => $offset,
        ], [
            'limit' => ParameterType::INTEGER,
            'offset' => ParameterType::INTEGER,
        ]);

        // SQL для получения общего количества групп
        $countSql = "
            SELECT COUNT(DISTINCT DATE_FORMAT(create_date, :format))
            FROM `orders`
        ";

        $total = (int) $conn->fetchOne($countSql, ['format' => $dateFormat]);

        return [
            'items' => array_map(fn($item) => [
                'period' => (string) $item['period'],
                'orderCount' => (int) $item['orderCount'],
                'totalAmount' => (float) ($item['totalAmount'] ?? 0),
            ], $items),
            'total' => $total,
        ];
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->getQuery()
            ->getSingleScalarResult();
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

    public function search(
        string $query,
        int $page = 1,
        int $limit = 10,
        ?int $lastId = null,
        ?int $status = null
    ): SearchResult
    {
        $queryDto = $this->queryBuilder->build($query, $page, $limit, $lastId, $status);

        $qb = $this->createQueryBuilder('o')
            ->select('o', 'a')
            ->leftJoin('o.articles', 'a');

        if (!empty($query)) {
            $qb->andWhere('o.number LIKE :query OR o.customerInfo.email LIKE :query OR o.customerInfo.name LIKE :query OR o.customerInfo.surname LIKE :query OR o.customerInfo.companyName LIKE :query OR o.description LIKE :query')
                ->setParameter('query', '%' . $query . '%');
        }

        if ($queryDto->status !== null) {
            $qb->andWhere('o.status = :status')
                ->setParameter('status', $queryDto->status);
        }

        // Count total matching items WITHOUT lastId filter
        $totalQuery = clone $qb;
        $total = (int)$totalQuery->select('COUNT(DISTINCT o.id)')
            ->setFirstResult(null)
            ->setMaxResults(null)
            ->getQuery()
            ->getSingleScalarResult();

        if ($queryDto->lastId !== null && $queryDto->lastId > 0) {
            $qb->andWhere('o.id < :lastId');
            $qb->setParameter('lastId', $queryDto->lastId);
        }

        foreach ($queryDto->sort as $field => $direction) {
            $qb->addOrderBy('o.' . $field, strtoupper($direction));
        }

        if (empty($queryDto->sort)) {
            // Default sort if none provided by builder
            $qb->addOrderBy('o.id', 'DESC');
        }

        $items = $qb
            ->setFirstResult($queryDto->offset)
            ->setMaxResults($queryDto->limit)
            ->getQuery()
            ->getResult();

        return new SearchResult($items, $total);
    }

    public function index(Order $order): void
    {
        // No-op for DB repository
    }

    public function delete(int $orderId): void
    {
        // No-op for DB repository
    }
}
