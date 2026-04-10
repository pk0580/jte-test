<?php

declare(strict_types=1);

namespace App\Domain\Service\Outbox;

use App\Domain\Enum\OrderEventType;

interface OutboxServiceInterface
{
    public function add(OrderEventType $type, int $orderId): void;
}
