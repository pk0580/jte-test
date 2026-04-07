<?php

declare(strict_types=1);

namespace App\Tests\Application\MessageHandler;

use App\Application\Message\SendOrderEmailMessage;
use App\Application\MessageHandler\SendOrderEmailHandler;
use App\Domain\Entity\Order;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\ValueObject\CustomerInfo;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;

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
        $cache = $this->createMock(CacheInterface::class);
        $handler = new SendOrderEmailHandler($repository, $logger, $cache);

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

        $this->assertTrue(true);
    }

    public function testHandlerOrderNotFound(): void
    {
        $orderId = 999;
        $repository = $this->createMock(OrderRepositoryInterface::class);
        $repository->method('findById')->with($orderId)->willReturn(null);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('not found'));

        $cache = $this->createMock(CacheInterface::class);
        $handler = new SendOrderEmailHandler($repository, $logger, $cache);

        $message = new SendOrderEmailMessage($orderId);
        ($handler)($message);
    }

    public function testHandlerCacheExceptionSilentlyFails(): void
    {
        $orderId = 123;
        $customerInfo = new CustomerInfo(email: 'test@example.com');
        $order = $this->createMock(Order::class);
        $order->method('getId')->willReturn($orderId);
        $order->method('getCustomerInfo')->willReturn($customerInfo);

        $repository = $this->createMock(OrderRepositoryInterface::class);
        $repository->method('findById')->with($orderId)->willReturn($order);

        $logger = $this->createMock(LoggerInterface::class);
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->willThrowException(new \Exception('Cache error'));

        $handler = new SendOrderEmailHandler($repository, $logger, $cache);
        $message = new SendOrderEmailMessage($orderId);

        ($handler)($message);
        $this->assertTrue(true); // Should not throw exception
    }
}
