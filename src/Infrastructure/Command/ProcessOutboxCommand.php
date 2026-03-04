<?php

namespace App\Infrastructure\Command;

use App\Domain\Dto\Outbox\OrderEventPayloadDto;
use App\Application\Message\DeleteOrderMessage;
use App\Application\Message\IndexOrderMessage;
use App\Domain\Entity\OutboxEvent;
use App\Domain\Enum\OrderEventType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\LockFactory;
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
        private readonly LockFactory $lockFactory
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $lockKey = 'outbox_process_lock';
        $lock = $this->lockFactory->createLock($lockKey);

        if (!$lock->acquire()) {
            $output->writeln('Command is already running.');
            return Command::SUCCESS;
        }

        try {
            $repository = $this->entityManager->getRepository(OutboxEvent::class);

            // Получаем необработанные события.
            // Мы не можем легко отфильтровать по backoff в findBy,
            // поэтому возьмем пачку и отфильтруем в PHP или используем QueryBuilder.
            $queryBuilder = $this->entityManager->createQueryBuilder();
            $queryBuilder->select('e')
                ->from(OutboxEvent::class, 'e')
                ->where('e.processedAt IS NULL')
                ->andWhere('e.attempts < :maxAttempts')
                ->setParameter('maxAttempts', 10)
                ->orderBy('e.createdAt', 'ASC')
                ->setMaxResults(100);

            $events = $queryBuilder->getQuery()->getResult();

            if (empty($events)) {
                return Command::SUCCESS;
            }

            $now = new \DateTimeImmutable();

            foreach ($events as $index => $event) {
                // Exponential Backoff: delay = 2^attempts * 60 seconds
                if ($event->getAttempts() > 0) {
                    $delaySeconds = (2 ** ($event->getAttempts() - 1)) * 60;
                    $nextAttemptAt = $event->getCreatedAt()->modify(sprintf('+%d seconds', $delaySeconds));

                    if ($nextAttemptAt > $now) {
                        continue;
                    }
                }

                try {
                    $event->incrementAttempts();

                    $payloadDto = $event->getPayloadDto();
                    $message = match ($event->getEventType()) {
                        OrderEventType::INDEXED => new IndexOrderMessage($payloadDto->id),
                        OrderEventType::DELETED => new DeleteOrderMessage($payloadDto->id),
                    };

                    if ($message) {
                        $this->messageBus->dispatch($message);
                    }

                    $event->setProcessedAt(new \DateTimeImmutable());
                    $event->setLastError(null);

                    // Flush every 20 events
                    if (($index + 1) % 20 === 0) {
                        $this->entityManager->flush();
                    }
                } catch (\Exception $e) {
                    $event->setLastError($e->getMessage());
                    $output->writeln(sprintf('Error processing event %d (attempt %d): %s',
                        $event->getId(),
                        $event->getAttempts(),
                        $e->getMessage()
                    ));
                }
            }

            $this->entityManager->flush();
        } finally {
            $lock->release();
        }

        return Command::SUCCESS;
    }
}
