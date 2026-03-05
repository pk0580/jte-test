<?php

namespace App\Domain\Contract;

use App\Domain\Event\DomainEventInterface;

interface HasDomainEventsInterface
{
    /** @return DomainEventInterface[] */
    public function pullDomainEvents(): array;
}
