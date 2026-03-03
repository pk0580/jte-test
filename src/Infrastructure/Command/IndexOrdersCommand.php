<?php

namespace App\Infrastructure\Command;

use Doctrine\DBAL\Connection;
use App\Domain\Repository\OrderSearchInterface;
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
        private readonly OrderSearchInterface $search,
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

        if (!$this->validateBatchSize($batchSize, $io)) {
            return Command::FAILURE;
        }

        try {
            $maxIdAtStart = (int)$this->connection->fetchOne("SELECT MAX(id) FROM orders");
            $tmpIndex = 'orders_tmp_' . date('YmdHis');

            $this->initReindex($tmpIndex, $maxIdAtStart, $io);

            $this->runMainPass($tmpIndex, $resumeId, $maxIdAtStart, $batchSize, $io);

            $this->runCatchUpPass($tmpIndex, $maxIdAtStart, $batchSize, $io);

            $this->finalizeReindex($tmpIndex, $io);

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    private function validateBatchSize(int $batchSize, SymfonyStyle $io): bool
    {
        if ($batchSize <= 0) {
            $io->error('Batch must be > 0');
            return false;
        }
        if ($batchSize > 10000) {
            $io->error('Batch too large. Max allowed: 10000');
            return false;
        }
        return true;
    }

    private function initReindex(string $tmpIndex, int $maxIdAtStart, SymfonyStyle $io): void
    {
        $io->title("Reindexing orders (Max ID at start: $maxIdAtStart)");
        $io->text("Creating temporary index: $tmpIndex");
        $this->search->createIndex($tmpIndex);
    }

    private function runMainPass(string $tmpIndex, int $resumeId, int $maxIdAtStart, int $batchSize, SymfonyStyle $io): void
    {
        $totalToProcess = $maxIdAtStart - $resumeId;

        if ($totalToProcess <= 0) {
            $io->note("No new orders to process for the main pass.");
            return;
        }

        $io->progressStart($totalToProcess);

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

            $io->progressAdvance($processedCount);

            if (self::THROTTLE_US > 0) {
                usleep(self::THROTTLE_US);
            }
        }

        $io->progressFinish();
    }

    private function runCatchUpPass(string $tmpIndex, int $maxIdAtStart, int $batchSize, SymfonyStyle $io): void
    {
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
    }

    private function finalizeReindex(string $tmpIndex, SymfonyStyle $io): void
    {
        $io->text("Swapping indexes...");
        $this->search->swapIndex($tmpIndex, 'orders');
        $io->success('Reindex complete (zero-downtime).');
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
