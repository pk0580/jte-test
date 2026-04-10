<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Event\DomainEventInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Диспетчер доменных событий прикладного уровня.
 *
 * Отвечает за отправку доменных событий через шину сообщений Symfony Messenger.
 * Позволяет делегировать обработку доменных событий специализированным обработчикам
 * вне жизненного цикла Doctrine.
 */
readonly class DomainEventDispatcher
{
    public function __construct(
        private MessageBusInterface $eventBus
    ) {
    }

    /**
     * @param DomainEventInterface[] $events
     * @throws ExceptionInterface
     */
    public function dispatch(array $events): void
    {
        foreach ($events as $event) {
            $this->eventBus->dispatch($event);
        }
    }

    /**
     * @throws ExceptionInterface
     */
    public function dispatchSingle(DomainEventInterface $event): void
    {
        $this->eventBus->dispatch($event);
    }
}
