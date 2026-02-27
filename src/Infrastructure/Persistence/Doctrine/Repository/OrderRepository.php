<?php

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\Order;
use App\Domain\Entity\OrderArticle;
use App\Domain\Repository\OrderRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository implements OrderRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
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
                SUM(t.totalAmount) as totalAmount
            FROM `orders` o
            LEFT JOIN (
                SELECT orders_id, SUM(amount * price) as totalAmount
                FROM `orders_article`
                GROUP BY orders_id
            ) t ON t.orders_id = o.id
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
            ->select('o.id, o.number, o.email, o.clientName, o.clientSurname, o.companyName, o.description')
            ->orderBy('o.id', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }
}
