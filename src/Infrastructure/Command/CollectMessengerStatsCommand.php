<?php

declare(strict_types=1);

namespace App\Infrastructure\Command;

use Doctrine\DBAL\Connection;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'app:collect-messenger-stats',
    description: 'Calculate messenger queue metrics and store in cache'
)]
class CollectMessengerStatsCommand extends Command
{
    private const string CACHE_KEY = 'messenger_queue_count';

    public function __construct(
        private readonly Connection $connection,
        private readonly CacheItemPoolInterface $metricsCache,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $io->comment('Calculating messenger queue messages...');

            // Тяжелый запрос SELECT COUNT(*)
            $count = (float) $this->connection->fetchOne('SELECT COUNT(*) FROM messenger_messages');

            $cacheItem = $this->metricsCache->getItem(self::CACHE_KEY);
            $cacheItem->set($count);
            // TTL не ставим жесткий в 10 сек, пусть лежит пока не обновится,
            // или можно поставить например 1 час для надежности.
            $cacheItem->expiresAfter(3600);
            $this->metricsCache->save($cacheItem);

            $io->success(sprintf('Collected: %f messages. Saved to cache.', $count));

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $io->error(sprintf('Error collecting metrics: %s', $e->getMessage()));
            return Command::FAILURE;
        }
    }
}
