<?php

namespace App\Domain\Event;

class OrderDeletedEvent implements DomainEventInterface
{
    public function __construct(
        private readonly int $orderId
    ) {}

    public function getOrderId(): int
    {
        return $this->orderId;
    }
}
