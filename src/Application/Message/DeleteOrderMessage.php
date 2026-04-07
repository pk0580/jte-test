<?php

declare(strict_types=1);

namespace App\Application\Message;

readonly class DeleteOrderMessage
{
    public function __construct(
        private int $orderId
    ) {
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }
}
