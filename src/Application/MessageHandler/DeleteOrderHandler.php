<?php

namespace App\Application\MessageHandler;

use App\Application\Message\DeleteOrderMessage;
use App\Domain\Repository\OrderSearchInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class DeleteOrderHandler
{
    public function __construct(
        private OrderSearchInterface $orderSearch
    ) {}

    public function __invoke(DeleteOrderMessage $message): void
    {
        $this->orderSearch->delete($message->getOrderId());
    }
}
