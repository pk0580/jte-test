<?php

namespace App\Application\Message;

readonly class IndexOrderMessage
{
    public function __construct(
        private int $orderId
    ) {}

    public function getOrderId(): int
    {
        return $this->orderId;
    }
}
