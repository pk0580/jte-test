<?php

namespace App\Tests\Domain\Entity;

use App\Domain\Entity\Order;
use App\Domain\Entity\OrderArticle;
use PHPUnit\Framework\TestCase;

class OrderTest extends TestCase
{
    public function testRecalculateTotals(): void
    {
        $order = new Order();

        $article1 = new OrderArticle();
        $article1->setPrice('100.50');
        $article1->setAmount('2');
        $article1->setWeight('1.5');

        $article2 = new OrderArticle();
        $article2->setPrice('50.00');
        $article2->setAmount('1');
        $article2->setWeight('2.0');

        $order->addArticle($article1);
        $order->addArticle($article2);

        $order->recalculateTotals();

        // (100.50 * 2) + (50.00 * 1) = 201 + 50 = 251.00
        $this->assertEquals('251.00', $order->getTotalAmount());

        // (1.5 * 2) + (2.0 * 1) = 3 + 2 = 5.0
        $this->assertEquals('5.000', $order->getTotalWeight());
    }

    public function testChangeStatus(): void
    {
        $order = new Order();
        $order->changeStatus(2);

        $this->assertEquals(2, $order->getStatus());
    }
}
