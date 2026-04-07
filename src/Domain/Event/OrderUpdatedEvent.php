<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\Entity\Order;

class OrderUpdatedEvent implements DomainEventInterface
{
    public function __construct(
        private readonly Order $order
    ) {
    }

    public function getOrder(): Order
    {
        return $this->order;
    }
}
