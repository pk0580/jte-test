<?php

namespace App\Tests\Application\MessageHandler;

use App\Application\Message\SendOrderEmailMessage;
use App\Application\MessageHandler\SendOrderEmailHandler;
use App\Domain\Entity\Order;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\ValueObject\CustomerInfo;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SendOrderEmailHandlerTest extends TestCase
{
    public function testHandlerLogsMessage(): void
    {
        $orderId = 123;
        $email = 'test@example.com';

        $customerInfo = new CustomerInfo(email: $email);

        $order = $this->createMock(Order::class);
        $order->method('getId')->willReturn($orderId);
        $order->method('getCustomerInfo')->willReturn($customerInfo);

        $repository = $this->createMock(OrderRepositoryInterface::class);
        $repository->method('findById')->with($orderId)->willReturn($order);

        $logger = $this->createMock(LoggerInterface::class);
        $handler = new SendOrderEmailHandler($repository, $logger);

        $message = new SendOrderEmailMessage($orderId);

        $logger->expects($this->once())
            ->method('info')
            ->with($this->callback(function (string $logMessage) use ($email, $orderId) {
                return str_contains($logMessage, $email)
                    && str_contains($logMessage, (string)$orderId)
                    && str_contains($logMessage, 'confirmed')
                    && str_contains($logMessage, 'order_confirmation.html.twig');
            }));

        ($handler)($message);
    }
}
