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
            $events = $repository->findBy(['processedAt' => null], ['createdAt' => 'ASC'], 100);

            if (empty($events)) {
                return Command::SUCCESS;
            }

            foreach ($events as $index => $event) {
                try {
                    $payloadDto = $event->getPayloadDto();
                    $message = match ($event->getEventType()) {
                        OrderEventType::INDEXED => new IndexOrderMessage($payloadDto->id),
                        OrderEventType::DELETED => new DeleteOrderMessage($payloadDto->id),
                    };

                    if ($message) {
                        $this->messageBus->dispatch($message);
                    }

                    $event->setProcessedAt(new \DateTimeImmutable());

                    // Flush every 20 events to provide progress and reduce memory usage
                    if (($index + 1) % 20 === 0) {
                        $this->entityManager->flush();
                    }
                } catch (\Exception $e) {
                    $output->writeln(sprintf('Error processing event %d: %s', $event->getId(), $e->getMessage()));
                }
            }

            $this->entityManager->flush();
        } finally {
            $lock->release();
        }

        return Command::SUCCESS;
    }
}
