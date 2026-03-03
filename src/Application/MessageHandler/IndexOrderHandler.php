<?php

namespace App\Application\MessageHandler;

use App\Application\Message\IndexOrderMessage;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Repository\OrderSearchInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class IndexOrderHandler
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private OrderSearchInterface $orderSearch
    ) {}

    public function __invoke(IndexOrderMessage $message): void
    {
        $order = $this->orderRepository->findById($message->getOrderId());
        if ($order) {
            $this->orderSearch->index($order);
        }
    }
}
