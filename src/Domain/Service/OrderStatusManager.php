<?php

namespace App\Domain\Service;

use App\Domain\Entity\Order;
use App\Domain\Exception\InvalidOrderStateException;
use App\Domain\ValueObject\OrderDates;

class OrderStatusManager
{
    private static array $allowedTransitions = [
        Order::STATUS_NEW => [Order::STATUS_PROCESSING, Order::STATUS_CANCELLED],
        Order::STATUS_PROCESSING => [Order::STATUS_SHIPPED, Order::STATUS_CANCELLED],
        Order::STATUS_SHIPPED => [Order::STATUS_DELIVERED],
        Order::STATUS_DELIVERED => [],
        Order::STATUS_CANCELLED => [],
    ];

    public function changeStatus(Order $order, int $newStatus): void
    {
        $currentStatus = $order->getStatus();

        if ($currentStatus === $newStatus) {
            return;
        }

        if (!isset(self::$allowedTransitions[$currentStatus]) || !in_array($newStatus, self::$allowedTransitions[$currentStatus], true)) {
            throw InvalidOrderStateException::transitionNotAllowed($currentStatus, $newStatus);
        }

        $order->setInternalStatus($newStatus);

        $dates = $order->getDates();
        $newDates = $dates->withUpdateAt(new \DateTime());

        if ($newStatus === Order::STATUS_CANCELLED) {
            $newDates = new OrderDates(
                $newDates->createAt,
                $newDates->updateAt,
                $newDates->payDateExecution,
                $newDates->offsetDate,
                $newDates->proposedDate,
                $newDates->shipDate,
                new \DateTime(),
                $newDates->fullPaymentDate
            );
        }

        $order->setDates($newDates);
    }
}
