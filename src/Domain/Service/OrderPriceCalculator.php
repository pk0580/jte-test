<?php

namespace App\Domain\Service;

use App\Domain\Entity\Order;

class OrderPriceCalculator
{
    public function recalculate(Order $order): void
    {
        $amount = '0.00';
        $weight = '0.000';

        foreach ($order->getArticles() as $article) {
            $amount = bcadd($amount, bcmul($article->getAmount(), $article->getPrice(), 10), 2);
            $weight = bcadd($weight, bcmul($article->getAmount(), $article->getWeight(), 10), 3);
        }

        $order->setTotalAmount($amount);
        $order->setTotalWeight($weight);
    }
}
