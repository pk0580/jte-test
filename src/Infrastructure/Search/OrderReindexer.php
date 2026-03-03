<?php

namespace App\Infrastructure\Search;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class OrderReindexer
{
    private const int DEFAULT_BATCH = 5000;
    private const int RETRIES = 3;
    private const int THROTTLE_US = 10000; // 10ms

    public function __construct(
        private readonly Connection $connection,
        private readonly SearchIndexerInterface $searchIndexer,
    ) {}

    public function reindex(?SymfonyStyle $io = null, int $batchSize = self::DEFAULT_BATCH, int $resumeId = 0): void
    {
        try {
            $maxIdAtStart = (int)$this->connection->fetchOne("SELECT MAX(id) FROM orders");
            $tmpIndex = 'orders_tmp_' . date('YmdHis');

            $this->initReindex($tmpIndex, $maxIdAtStart, $io);
            $this->runMainPass($tmpIndex, $resumeId, $maxIdAtStart, $batchSize, $io);
            $this->runCatchUpPass($tmpIndex, $maxIdAtStart, $batchSize, $io);
            $this->finalizeReindex($tmpIndex, $io);

        } catch (Throwable $e) {
            if ($io) {
                $io->error($e->getMessage());
            }
            throw $e;
        }
    }

    private function initReindex(string $tmpIndex, int $maxIdAtStart, ?SymfonyStyle $io): void
    {
        if ($io) {
            $io->title("Reindexing orders (Max ID at start: $maxIdAtStart)");
            $io->text("Creating temporary index: $tmpIndex");
        }
        $this->searchIndexer->createIndex($tmpIndex);
    }

    private function runMainPass(string $tmpIndex, int $resumeId, int $maxIdAtStart, int $batchSize, ?SymfonyStyle $io): void
    {
        $totalToProcess = $maxIdAtStart - $resumeId;

        if ($totalToProcess <= 0) {
            if ($io) {
                $io->note("No new orders to process for the main pass.");
            }
            return;
        }

        if ($io) {
            $io->progressStart($totalToProcess);
        }

        $lastId = $resumeId;
        while ($lastId < $maxIdAtStart) {
            $rows = $this->fetchBatch($lastId, $batchSize, $maxIdAtStart);
            if (!$rows) {
                break;
            }

            $this->retryBulk($tmpIndex, $rows, $io);

            $batchLastId = (int)end($rows)['id'];
            $processedCount = count($rows);
            $lastId = $batchLastId;

            if ($io) {
                $io->progressAdvance($processedCount);
            }

            if (self::THROTTLE_US > 0) {
                usleep(self::THROTTLE_US);
            }
        }

        if ($io) {
            $io->progressFinish();
        }
    }

    private function runCatchUpPass(string $tmpIndex, int $maxIdAtStart, int $batchSize, ?SymfonyStyle $io): void
    {
        if ($io) {
            $io->section("Catch-up pass (syncing concurrent changes)");
        }
        $catchUpLastId = $maxIdAtStart;

        while (true) {
            $rows = $this->fetchBatch($catchUpLastId, $batchSize);
            if (!$rows) {
                break;
            }
            $this->retryBulk($tmpIndex, $rows, $io);
            $catchUpLastId = (int)end($rows)['id'];
            if ($io) {
                $io->text("Indexed new orders up to ID: $catchUpLastId");
            }
        }
    }

    private function finalizeReindex(string $tmpIndex, ?SymfonyStyle $io): void
    {
        if ($io) {
            $io->text("Swapping indexes...");
        }
        $this->searchIndexer->swapIndex($tmpIndex, 'orders');
        if ($io) {
            $io->success('Reindex complete (zero-downtime).');
        }
    }

    private function fetchBatch(int $lastId, int $limit, ?int $maxId = null): array
    {
        $sql = "SELECT id, number, email, client_name, client_surname, company_name, description, status
                FROM orders WHERE id > :lastId";
        $params = ['lastId' => $lastId, 'limit' => $limit];
        $types = ['limit' => ParameterType::INTEGER];

        if ($maxId !== null) {
            $sql .= " AND id <= :maxId";
            $params['maxId'] = $maxId;
        }

        $sql .= " ORDER BY id ASC LIMIT :limit";

        return $this->connection->fetchAllAssociative($sql, $params, $types);
    }

    private function retryBulk(string $index, array $rows, ?SymfonyStyle $io): void
    {
        $attempt = 0;

        while ($attempt < self::RETRIES) {
            try {
                $this->searchIndexer->bulkIndexRawToIndex($index, $rows);
                return;
            } catch (Throwable $e) {
                $attempt++;
                if ($io) {
                    $io->warning(sprintf(
                        "Batch failed (ID %d - %d), attempt %d/%d: %s",
                        $rows[0]['id'],
                        end($rows)['id'],
                        $attempt,
                        self::RETRIES,
                        $e->getMessage()
                    ));
                }

                if ($attempt >= self::RETRIES) {
                    throw $e;
                }
                usleep(500000 * $attempt);
            }
        }
    }
}
