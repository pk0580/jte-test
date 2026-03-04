<?php

namespace App\Infrastructure\Command;

use App\Application\Message\DeleteOrderMessage;
use App\Application\Message\IndexOrderMessage;
use App\Domain\Entity\OutboxEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:outbox:process',
    description: 'Processes events from outbox table and sends them to messenger.'
)]
class ProcessOutboxCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $messageBus,
        private readonly \Symfony\Contracts\Cache\CacheInterface $cache
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $lockKey = 'outbox_process_lock';
        $lockAcquired = false;

        $this->cache->get($lockKey, function (\Symfony\Contracts\Cache\ItemInterface $item) use (&$lockAcquired) {
            $item->expiresAfter(60); // Блокировка на 60 секунд
            $lockAcquired = true;
            return true;
        }, 0.0);

        if (!$lockAcquired) {
            $output->writeln('Command is already running.');
            return Command::SUCCESS;
        }

        try {
            $repository = $this->entityManager->getRepository(OutboxEvent::class);
            $events = $repository->findBy(['processedAt' => null], ['createdAt' => 'ASC'], 100);

            if (empty($events)) {
                return Command::SUCCESS;
            }

            foreach ($events as $event) {
                try {
                    $payload = $event->getPayload();
                    $message = match ($event->getEventType()) {
                        'order.indexed' => new IndexOrderMessage($payload['id']),
                        'order.deleted' => new DeleteOrderMessage($payload['id']),
                        default => null,
                    };

                    if ($message) {
                        $this->messageBus->dispatch($message);
                    }

                    $event->setProcessedAt(new \DateTimeImmutable());
                } catch (\Exception $e) {
                    $output->writeln(sprintf('Error processing event %d: %s', $event->getId(), $e->getMessage()));
                }
            }

            $this->entityManager->flush();
        } finally {
            $this->cache->delete($lockKey);
        }

        return Command::SUCCESS;
    }
}
