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
        $lastId = (int)$input->getOption('resume-from-id');

        if ($batchSize <= 0) {
            $io->error('Batch must be > 0');
            return Command::FAILURE;
        } elseif ($batchSize > 10000) {
            $io->error('Batch too large. Max allowed: 10000');
            return Command::FAILURE;
        }

        $tmpIndex = 'orders_tmp_' . date('YmdHis');

        try {
            $io->title("Creating temporary index: $tmpIndex");
            $this->search->createIndex($tmpIndex);

            while (true) {
                $rows = $this->connection->fetchAllAssociative(
                    "
                    SELECT id, number, email, client_name, client_surname,
                           company_name, description
                    FROM orders
                    WHERE id > :lastId
                    ORDER BY id ASC
                    LIMIT :limit
                    ",
                    [
                        'lastId' => $lastId,
                        'limit' => $batchSize,
                    ],
                    [
                        'limit' => ParameterType::INTEGER,
                    ]
                );

                if (!$rows) {
                    break;
                }

                $this->retryBulk($tmpIndex, $rows);

                $lastId = (int)end($rows)['id'];

                $io->write("\rIndexed up to ID: $lastId");

                // Throttling to avoid overloading Manticore
                if (self::THROTTLE_US > 0) {
                    usleep(self::THROTTLE_US);
                }
            }

            $io->newLine();
            $io->text("Swapping indexes...");

            $this->search->swapIndex($tmpIndex, 'orders');

            $io->success('Reindex complete (zero-downtime).');

            return Command::SUCCESS;

        } catch (Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    private function retryBulk(string $index, array $rows): void
    {
        $attempt = 0;

        while ($attempt < self::RETRIES) {
            try {
                $this->search->bulkIndexRawToIndex($index, $rows);
                return;
            } catch (Throwable $e) {
                $attempt++;
                if ($attempt >= self::RETRIES) {
                    throw $e;
                }
                usleep(500000 * $attempt);
            }
        }

        throw new \RuntimeException('Bulk failed after retries');
    }
}
