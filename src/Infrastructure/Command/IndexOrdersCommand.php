<?php

namespace App\Infrastructure\Command;

use Doctrine\DBAL\Connection;
use App\Infrastructure\Search\ManticoreOrderSearch;
use Doctrine\DBAL\ParameterType;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'app:index-orders',
    description: 'Zero-downtime reindex for 10M+ records'
)]
class IndexOrdersCommand extends Command
{
    private const int DEFAULT_BATCH = 5000;
    private const int RETRIES = 3;
    private const int THROTTLE_US = 10000; // 10ms

    public function __construct(
        private readonly Connection $connection,
        private readonly ManticoreOrderSearch $search,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('batch', null, InputOption::VALUE_OPTIONAL, 'Batch size', self::DEFAULT_BATCH)
            ->addOption('resume-from-id', null, InputOption::VALUE_OPTIONAL, 'Resume from ID', 0);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $batchSize = (int)$input->getOption('batch');
        $resumeId = (int)$input->getOption('resume-from-id');

        if ($batchSize <= 0) {
            $io->error('Batch must be > 0');
            return Command::FAILURE;
        } elseif ($batchSize > 10000) {
            $io->error('Batch too large. Max allowed: 10000');
            return Command::FAILURE;
        }

        // 1. Get current max ID for "catch-up" later
        $maxIdAtStart = (int)$this->connection->fetchOne("SELECT MAX(id) FROM orders");
        $totalToProcess = $maxIdAtStart - $resumeId;

        $tmpIndex = 'orders_tmp_' . date('YmdHis');

        try {
            $io->title("Reindexing orders (Max ID at start: $maxIdAtStart)");
            $io->text("Creating temporary index: $tmpIndex");
            $this->search->createIndex($tmpIndex);

            if ($totalToProcess > 0) {
                $io->progressStart($totalToProcess);
            } else {
                $io->note("No new orders to process for the main pass.");
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

                if ($totalToProcess > 0) {
                    $io->progressAdvance($processedCount);
                }

                // Throttling to avoid overloading Manticore
                if (self::THROTTLE_US > 0) {
                    usleep(self::THROTTLE_US);
                }
            }

            if ($totalToProcess > 0) {
                $io->progressFinish();
            }

            // 2. Catch-up pass for orders created during the main pass
            $io->section("Catch-up pass (syncing concurrent changes)");
            $catchUpLastId = $maxIdAtStart;
            while (true) {
                $rows = $this->fetchBatch($catchUpLastId, $batchSize);
                if (!$rows) {
                    break;
                }
                $this->retryBulk($tmpIndex, $rows, $io);
                $catchUpLastId = (int)end($rows)['id'];
                $io->text("Indexed new orders up to ID: $catchUpLastId");
            }

            $io->text("Swapping indexes...");
            $this->search->swapIndex($tmpIndex, 'orders');

            $io->success('Reindex complete (zero-downtime).');

            return Command::SUCCESS;

        } catch (Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    private function fetchBatch(int $lastId, int $limit, ?int $maxId = null): array
    {
        $sql = "SELECT id, number, email, client_name, client_surname, company_name, description
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

    private function retryBulk(string $index, array $rows, SymfonyStyle $io): void
    {
        $attempt = 0;

        while ($attempt < self::RETRIES) {
            try {
                $this->search->bulkIndexRawToIndex($index, $rows);
                return;
            } catch (Throwable $e) {
                $attempt++;
                $io->warning(sprintf(
                    "Batch failed (ID %d - %d), attempt %d/%d: %s",
                    $rows[0]['id'],
                    end($rows)['id'],
                    $attempt,
                    self::RETRIES,
                    $e->getMessage()
                ));

                if ($attempt >= self::RETRIES) {
                    throw $e;
                }
                usleep(500000 * $attempt);
            }
        }
    }
}
