<?php

namespace App\Infrastructure\Command;

use App\Infrastructure\Search\OrderReindexer;
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

    public function __construct(
        private readonly OrderReindexer $reindexer,
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
            $this->reindexer->reindex($io, $batchSize, $resumeId);

            return Command::SUCCESS;
        } catch (Throwable $e) {
            // Error is already reported by reindexer if $io is passed
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
}
