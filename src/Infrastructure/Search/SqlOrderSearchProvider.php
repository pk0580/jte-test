<?php

namespace App\Infrastructure\Search;

use App\Domain\Dto\Search\SearchOrderDto;
use App\Domain\Entity\Order;
use App\Domain\Repository\OrderSearchInterface;
use App\Domain\Repository\SearchResult;
use Doctrine\ORM\EntityManagerInterface;

readonly class SqlOrderSearchProvider implements OrderSearchInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OrderSearchQueryBuilder $queryBuilder
    ) {}

    /**
     * @param string $query
     * @param int $page
     * @param int $limit
     * @param int|null $lastId
     * @param int|null $status
     * @return SearchResult<SearchOrderDto>
     */
    public function search(
        string $query,
        int $page = 1,
        int $limit = 10,
        ?int $lastId = null,
        ?int $status = null
    ): SearchResult
    {
        $queryDto = $this->queryBuilder->build($query, $page, $limit, $lastId, $status);

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('o')
            ->from(Order::class, 'o');

        if ($queryDto->status !== null) {
            $qb->andWhere('o.status = :status')
                ->setParameter('status', $queryDto->status);
        }

        // Count total matching items
        $totalQuery = clone $qb;
        $total = (int)$totalQuery->select('COUNT(o.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $qb->select('o'); // Remove fetching articles for search listing optimization

        if ($queryDto->lastId !== null && $queryDto->lastId > 0) {
            $qb->andWhere('o.id < :lastId');
            $qb->setParameter('lastId', $queryDto->lastId);
        }

        foreach ($queryDto->sort as $field => $direction) {
            $qb->addOrderBy('o.' . $field, strtoupper($direction));
        }

        if (empty($queryDto->sort)) {
            $qb->addOrderBy('o.id', 'DESC');
        }

        /** @var Order[] $items */
        $items = $qb
            ->setFirstResult($queryDto->offset)
            ->setMaxResults($queryDto->limit)
            ->getQuery()
            ->getResult();

        $dtos = array_map(function (Order $order) {
            return new SearchOrderDto(
                id: $order->getId(),
                number: $order->getNumber() ?? '',
                email: $order->getEmail() ?? '',
                clientName: $order->getClientName() ?? '',
                clientSurname: $order->getClientSurname() ?? '',
                companyName: $order->getCompanyName() ?? '',
                description: $order->getDescription() ?? '',
                status: $order->getStatus(),
            );
        }, $items);

        return new SearchResult($dtos, $total);
    }

    public function index(Order $order): void
    {
        // No-op for SQL
    }

    public function delete(int $orderId): void
    {
        // No-op for SQL
    }

    public function ping(): bool
    {
        return true;
    }
}
