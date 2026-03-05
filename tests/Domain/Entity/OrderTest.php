<?php

namespace App\Tests\Domain\Entity;

use App\Domain\Entity\Order;
use App\Domain\Entity\OrderArticle;
use App\Domain\Entity\Article;
use App\Domain\Entity\PayType;
use App\Domain\ValueObject\CustomerInfo;
use App\Domain\ValueObject\DeliveryAddress;
use App\Domain\ValueObject\DeliveryConfig;
use App\Domain\ValueObject\DeliveryTerms;
use App\Domain\ValueObject\FinancialTerms;
use App\Domain\ValueObject\ManagerInfo;
use App\Domain\Exception\InvalidOrderStateException;
use PHPUnit\Framework\TestCase;

class OrderTest extends TestCase
{
    private function createOrder(): Order
    {
        return new Order(
            payType: new PayType('Test Pay'),
            name: 'Test Order',
            customerInfo: new CustomerInfo(),
            deliveryAddress: new DeliveryAddress(),
            deliveryTerms: new DeliveryTerms(),
            managerInfo: new ManagerInfo(),
            financialTerms: new FinancialTerms(),
            deliveryConfig: new DeliveryConfig()
        );
    }

    public function testRecalculateTotals(): void
    {
        $order = $this->createOrder();
        $articleEntity = new Article('Test', '100.50', '1.5');

        $article1 = new OrderArticle(
            order: $order,
            article: $articleEntity,
            amount: '2',
            price: '100.50',
            weight: '1.5',
            packagingCount: '1',
            pallet: '0',
            packaging: 'box'
        );

        $article2 = new OrderArticle(
            order: $order,
            article: $articleEntity,
            amount: '1',
            price: '50.00',
            weight: '2.0',
            packagingCount: '1',
            pallet: '0',
            packaging: 'box'
        );

        $order->addArticle($article1);
        $order->addArticle($article2);
        $order->recalculateTotals(new \App\Domain\Service\OrderPriceCalculator());

        // (100.50 * 2) + (50.00 * 1) = 201 + 50 = 251.00
        $this->assertEquals('251.00', $order->getPricing()->totalAmount);

        // (1.5 * 2) + (2.0 * 1) = 3 + 2 = 5.000
        $this->assertEquals('5.000', $order->getPricing()->totalWeight);

        // Update amount should trigger recalculate
        $article1->setAmount('3');
        $order->recalculateTotals(new \App\Domain\Service\OrderPriceCalculator());
        // (100.50 * 3) + (50.00 * 1) = 301.5 + 50 = 351.50
        $this->assertEquals('351.50', $order->getPricing()->totalAmount);
    }

    public function testIncapsulation(): void
    {
        $customerInfo = new CustomerInfo(name: 'Ivan', surname: 'Ivanov');
        $order = new Order(
            payType: new PayType('Test Pay'),
            name: 'Test Order',
            customerInfo: $customerInfo,
            deliveryAddress: new DeliveryAddress(),
            deliveryTerms: new DeliveryTerms(),
            managerInfo: new ManagerInfo(),
            financialTerms: new FinancialTerms(),
            deliveryConfig: new DeliveryConfig()
        );

        $this->assertEquals('Ivan', $order->getCustomerInfo()->name);
        $this->assertEquals('Ivanov', $order->getCustomerInfo()->surname);

        $order->assignNumber('ORD-123');
        $this->assertEquals('ORD-123', $order->getNumber());

        $this->assertNotEmpty($order->getMetadata()->hash);
        $this->assertNotEmpty($order->getMetadata()->token);
    }

    public function testStatusChangeFlow(): void
    {
        $order = $this->createOrder();
        $this->assertEquals(Order::STATUS_NEW, $order->getStatus());

        $statusManager = new \App\Domain\Service\OrderStatusManager();

        $order->changeStatus($statusManager, Order::STATUS_PROCESSING);
        $this->assertEquals(Order::STATUS_PROCESSING, $order->getStatus());

        $order->changeStatus($statusManager, Order::STATUS_SHIPPED);
        $this->assertEquals(Order::STATUS_SHIPPED, $order->getStatus());

        $order->changeStatus($statusManager, Order::STATUS_DELIVERED);
        $this->assertEquals(Order::STATUS_DELIVERED, $order->getStatus());
    }

    public function testInvalidStatusTransition(): void
    {
        $order = $this->createOrder();

        $this->expectException(InvalidOrderStateException::class);
        $order->changeStatus(new \App\Domain\Service\OrderStatusManager(), Order::STATUS_DELIVERED);
    }

    public function testCancelation(): void
    {
        $order = $this->createOrder();
        $order->changeStatus(new \App\Domain\Service\OrderStatusManager(), Order::STATUS_CANCELLED);

        $this->assertEquals(Order::STATUS_CANCELLED, $order->getStatus());
        $this->assertNotNull($order->getDates()->cancelDate);
    }
}
