<?php

namespace App\Infrastructure\Search;

use App\Domain\Entity\Order;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Repository\OrderSearchInterface;
use Manticoresearch\Client;

class ManticoreOrderSearch implements OrderSearchInterface
{
    private const string INDEX = 'orders';
    private const int MAX_SQL_BYTES = 4000000; // 4MB safe chunk
    private Client $client;

    public function __construct(
        string $host,
        int $port,
        private readonly OrderRepositoryInterface $orderRepository
    ) {
        $this->client = new Client(['host' => $host, 'port' => $port]);
    }

    public function search(string $query, int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;

        $escapedQuery = $this->escape($query);

        $sql = "SELECT id FROM " . self::INDEX . " WHERE MATCH('$escapedQuery') LIMIT $offset, $limit";
        $response = $this->client->sql($sql, true);

        $ids = [];
        if (is_array($response)) {
            // Manticore client in raw mode for SQL SELECT sometimes returns simple [id => id] array
            // or [{"data": [...]}] depending on library version/configuration.
            if (isset($response[0]['data'])) {
                foreach ($response[0]['data'] as $row) {
                    if (isset($row['id'])) {
                        $ids[] = (int)$row['id'];
                    }
                }
            } else {
                foreach ($response as $key => $value) {
                    if (is_int($value)) {
                        $ids[] = $value;
                    } elseif (is_array($value) && isset($value['id'])) {
                        $ids[] = (int)$value['id'];
                    }
                }
            }
        } elseif (is_object($response) && method_exists($response, 'getResponse')) {
            $data = $response->getResponse()->getData();
            if (is_array($data)) {
                foreach ($data as $row) {
                    if (isset($row['id'])) {
                        $ids[] = (int)$row['id'];
                    }
                }
            }
        }

        if (empty($ids)) {
            return [];
        }

        // Use findByIds to avoid N+1 and pre-fetch articles
        $orders = $this->orderRepository->findByIds($ids);

        // Sort by the order returned by search engine (if needed, though findByIds might not preserve order)
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
        $this->bulkIndexRawToIndex(self::INDEX, [[
            'id' => $order->getId(),
            'number' => $order->getNumber() ?? '',
            'email' => $order->getEmail() ?? '',
            'client_name' => $order->getClientName() ?? '',
            'client_surname' => $order->getClientSurname() ?? '',
            'company_name' => $order->getCompanyName() ?? '',
            'description' => $order->getDescription() ?? '',
        ]]);
    }

    public function delete(int $orderId): void
    {
        $this->client->sql("DELETE FROM " . self::INDEX . " WHERE id = $orderId", true);
    }

    public function recreateIndex(): void
    {
        $this->createIndex(self::INDEX);
    }

    public function createIndex(string $index): void
    {
        $this->client->sql("DROP TABLE IF EXISTS $index", true);
        $this->client->sql("
            CREATE TABLE $index (
                number text,
                email text,
                client_name text,
                client_surname text,
                company_name text,
                description text
            ) min_infix_len='3'
        ", true);
    }

    public function bulkIndexRaw(array $documents): void
    {
        $this->bulkIndexRawToIndex(self::INDEX, $documents);
    }

    public function bulkIndexRawToIndex(string $index, array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        $baseSql = "REPLACE INTO $index (id, number, email, client_name, client_surname, company_name, description) VALUES ";
        $currentValues = [];
        $currentSqlLength = strlen($baseSql);

        foreach ($rows as $row) {
            $id = (int)$row['id'];
            $number = $this->escape((string)($row['number'] ?? ''));
            $email = $this->escape((string)($row['email'] ?? ''));
            $client_name = $this->escape((string)($row['client_name'] ?? $row['clientName'] ?? ''));
            $client_surname = $this->escape((string)($row['client_surname'] ?? $row['clientSurname'] ?? ''));
            $company_name = $this->escape((string)($row['company_name'] ?? $row['companyName'] ?? ''));
            $description = $this->escape((string)($row['description'] ?? ''));

            $value = "($id, '$number', '$email', '$client_name', '$client_surname', '$company_name', '$description')";
            $valueLength = strlen($value) + 2; // +2 for ", " separator

            if (!empty($currentValues) && ($currentSqlLength + $valueLength) > self::MAX_SQL_BYTES) {
                $this->client->sql($baseSql . implode(', ', $currentValues), true);
                $currentValues = [];
                $currentSqlLength = strlen($baseSql);
            }

            $currentValues[] = $value;
            $currentSqlLength += $valueLength;
        }

        if (!empty($currentValues)) {
            $this->client->sql($baseSql . implode(', ', $currentValues), true);
        }
    }

    public function swapIndex(string $tmp, string $main): void
    {
        // Atomic swap for Manticore Search:
        // Use ALTER TABLE main RENAME TO main_old, tmp RENAME TO main
        // This is near-atomic and handles renaming multiple tables in one operation.
        try {
            $this->client->sql("DROP TABLE IF EXISTS {$main}_old", true);
            $this->client->sql("ALTER TABLE $main RENAME TO {$main}_old, $tmp RENAME TO $main", true);
            $this->client->sql("DROP TABLE IF EXISTS {$main}_old", true);
        } catch (\Throwable) {
            // Main doesn't exist yet (first run), just rename tmp to main
            $this->client->sql("ALTER TABLE $tmp RENAME TO $main", true);
        }
    }

    private function escape(string $value): string
    {
        return str_replace("'", "''", $value);
    }
}
