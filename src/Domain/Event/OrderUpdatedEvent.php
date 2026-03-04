<?php

namespace App\Domain\Event;

use App\Domain\Entity\Order;

class OrderUpdatedEvent implements DomainEventInterface
{
    public function __construct(
        private readonly Order $order
    ) {}

    public function getOrder(): Order
    {
        return $this->order;
    }
}
