<?php

namespace App\Infrastructure\Search;

use App\Domain\Entity\Order;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Repository\OrderSearchInterface;
use App\Domain\Repository\SearchResult;
use Manticoresearch\Client;
use Psr\Log\LoggerInterface;

class ManticoreOrderSearch implements OrderSearchInterface, SearchIndexerInterface
{
    private const string INDEX = 'orders';
    private const int MAX_SQL_BYTES = 4000000; // 4MB safe chunk
    private Client $client;

    public function __construct(
        string $host,
        int $port,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderSearchQueryBuilder $queryBuilder,
        private readonly LoggerInterface $logger
    ) {
        $this->client = new Client(['host' => $host, 'port' => $port]);
    }

    /**
     * @param string $query
     * @param int $page
     * @param int $limit
     * @param int|null $lastId
     * @param int|null $status
     * @return SearchResult<Order>
     */
    public function search(string $query, int $page = 1, int $limit = 10, ?int $lastId = null, ?int $status = null): SearchResult
    {
        try {
            $queryDto = $this->queryBuilder->build($query, $page, $limit, $lastId, $status);
            $search = $this->client->table(self::INDEX)->search($queryDto->query);

            if ($queryDto->status !== null) {
                $search->filter('status', 'equals', $queryDto->status);
            }

            foreach ($queryDto->sort as $field => $direction) {
                $search->sort($field, $direction);
            }

            if ($queryDto->lastId !== null && $queryDto->lastId > 0) {
                $search->filter('id', 'lt', $queryDto->lastId);
            }

            $resultSet = $search
                ->offset($queryDto->offset)
                ->limit($queryDto->limit)
                ->get();

            $ids = $this->fetchIds($resultSet);

            if (empty($ids)) {
                return new SearchResult([], 0);
            }

            $sortedOrders = $this->hydrateOrders($ids);

            return new SearchResult($sortedOrders, $resultSet->getTotal());
        } catch (\Throwable $e) {
            $this->logger->error('Manticore Search failed: ' . $e->getMessage(), [
                'query' => $query,
                'page' => $page,
                'limit' => $limit,
                'last_id' => $lastId,
                'status' => $status,
                'exception' => $e
            ]);

            // Fallback to DB search
            return $this->orderRepository->search($query, $page, $limit, $lastId, $status);
        }
    }

    private function fetchIds(\Manticoresearch\ResultSet $resultSet): array
    {
        $ids = [];
        foreach ($resultSet as $hit) {
            $ids[] = (int)$hit->getId();
        }
        return $ids;
    }

    /**
     * @param int[] $ids
     * @return Order[]
     */
    private function hydrateOrders(array $ids): array
    {
        // Use findByIds to avoid N+1 and pre-fetch articles
        $orders = $this->orderRepository->findByIds($ids);

        // Sort by the order returned by search engine
        $orderMap = [];
        foreach ($orders as $order) {
            $orderMap[$order->getId()] = $order;
        }

        $sortedOrders = [];
        foreach ($ids as $id) {
            if (isset($orderMap[$id])) {
                $sortedOrders[] = $orderMap[$id];
            }
        }

        return $sortedOrders;
    }

    public function index(Order $order): void
    {
        try {
            $id = $order->getId();
            $doc = [
                'number' => $order->getNumber() ?? '',
                'email' => $order->getEmail() ?? '',
                'client_name' => $order->getClientName() ?? '',
                'client_surname' => $order->getClientSurname() ?? '',
                'company_name' => $order->getCompanyName() ?? '',
                'description' => $order->getDescription() ?? '',
                'status' => $order->getStatus(),
            ];

            $this->client->bulk(['body' => [
                ['replace' => ['index' => self::INDEX, 'id' => $id, 'doc' => $doc]]
            ]]);
        } catch (\Throwable $e) {
            $this->logger->error('Manticore Indexing failed: ' . $e->getMessage(), [
                'order_id' => $order->getId(),
                'exception' => $e
            ]);
        }
    }

    public function delete(int $orderId): void
    {
        try {
            $this->client->sql("DELETE FROM " . self::INDEX . " WHERE id = $orderId", true);
        } catch (\Throwable $e) {
            $this->logger->error('Manticore Delete failed: ' . $e->getMessage(), [
                'order_id' => $orderId,
                'exception' => $e
            ]);
        }
    }

    public function recreateIndex(): void
    {
        $this->createIndex(self::INDEX);
    }

    public function createIndex(string $index): void
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $index)) {
            throw new \InvalidArgumentException('Invalid index name');
        }

        try {
            $this->client->sql("DROP TABLE IF EXISTS `$index`", true);

            $this->client->sql("CREATE TABLE `$index` (
                    number text,
                    email text,
                    client_name text,
                    client_surname text,
                    company_name text,
                    description text,
                    status int
                ) id='bigint' min_infix_len='3'", true);
        } catch (\Throwable $e) {
            $this->logger->error('Manticore Create Index failed: ' . $e->getMessage(), [
                'index' => $index,
                'exception' => $e
            ]);
            throw $e; // Re-throw for commands to handle
        }
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    public function bulkIndexRawToIndex(string $index, array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        $currentBatch = [];
        $currentBatchSize = 0;

        try {
            foreach ($rows as $row) {
                /** @var int $id */
                $id = (int)($row['id'] ?? 0);
                $doc = [
                    'number' => (string)($row['number'] ?? ''),
                    'email' => (string)($row['email'] ?? ''),
                    'client_name' => (string)($row['client_name'] ?? $row['clientName'] ?? ''),
                    'client_surname' => (string)($row['client_surname'] ?? $row['clientSurname'] ?? ''),
                    'company_name' => (string)($row['company_name'] ?? $row['companyName'] ?? ''),
                    'description' => (string)($row['description'] ?? ''),
                    'status' => (int)($row['status'] ?? 0),
                ];

                $op = ['replace' => ['index' => $index, 'id' => $id, 'doc' => $doc]];
                $opJson = json_encode($op, JSON_THROW_ON_ERROR);
                $opJsonSize = strlen($opJson);

                // If adding this op exceeds MAX_SQL_BYTES, send current batch first
                // We use 0.9 margin to be safe and account for bulk wrapper overhead
                if (!empty($currentBatch) && ($currentBatchSize + $opJsonSize) > (self::MAX_SQL_BYTES * 0.9)) {
                    $this->client->bulk(['body' => $currentBatch]);
                    $currentBatch = [];
                    $currentBatchSize = 0;
                }

                $currentBatch[] = $op;
                $currentBatchSize += $opJsonSize;

                // Also limit by a reasonable number of documents to avoid huge JSON structures
                if (count($currentBatch) >= 1000) {
                    $this->client->bulk(['body' => $currentBatch]);
                    $currentBatch = [];
                    $currentBatchSize = 0;
                }
            }

            if (!empty($currentBatch)) {
                $this->client->bulk(['body' => $currentBatch]);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Manticore Bulk Indexing failed: ' . $e->getMessage(), [
                'index' => $index,
                'count' => count($rows),
                'exception' => $e
            ]);
            throw $e; // Re-throw for commands to handle
        }
    }

    public function swapIndex(string $tmp, string $main): void
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $tmp)) {
            throw new \InvalidArgumentException('Invalid tmp index name');
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $main)) {
            throw new \InvalidArgumentException('Invalid main index name');
        }

        $mainOld = "{$main}_old";
        $mainExists = false;

        try {
            // 1. Try to rename main to main_old if it exists
            try {
                $this->client->sql("DESCRIBE `$main`", true);
                $mainExists = true;
            } catch (\Throwable) {
                // Main does not exist, it's fine
            }

            if ($mainExists) {
                $this->client->sql("DROP TABLE IF EXISTS `$mainOld`", true);
                $this->client->sql("ALTER TABLE `$main` RENAME TO `$mainOld`", true);
            }

            // 2. Try to rename tmp to main
            try {
                $this->client->sql("ALTER TABLE `$tmp` RENAME TO `$main`", true);
            } catch (\Throwable $renameError) {
                // If rename tmp -> main fails, try to restore main from main_old
                if ($mainExists) {
                    try {
                        $this->client->sql("ALTER TABLE `$mainOld` RENAME TO `$main`", true);
                    } catch (\Throwable $restoreError) {
                        $this->logger->critical('CRITICAL: Manticore failed to restore main index after failed swap!', [
                            'main' => $main,
                            'main_old' => $mainOld,
                            'restore_error' => $restoreError->getMessage(),
                            'swap_error' => $renameError->getMessage()
                        ]);
                    }
                }
                throw $renameError;
            }

            // 3. Drop old table
            if ($mainExists) {
                $this->client->sql("DROP TABLE IF EXISTS `$mainOld`", true);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Manticore Swap Index failed: ' . $e->getMessage(), [
                'tmp' => $tmp,
                'main' => $main,
                'exception' => $e
            ]);
            throw $e;
        }
    }

    public function ping(): bool
    {
        try {
            $this->client->sql('SELECT 1', true);
            return true;
        } catch (\Throwable $e) {
            $this->logger->warning('Manticore Health-check failed: ' . $e->getMessage());
            return false;
        }
    }
}
