<?php

namespace App\Tests\Controller;

use App\Domain\Entity\Order;
use App\Domain\Entity\OrderArticle;
use App\Domain\Repository\OrderRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OrderControllerTest extends WebTestCase
{
    public function testGetOrder(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        /** @var OrderRepositoryInterface $repository */
        $repository = $container->get(OrderRepositoryInterface::class);

        // Создаем тестовый заказ
        $order = new Order();
        $order->setHash(bin2hex(random_bytes(16)));
        $order->setToken(bin2hex(random_bytes(32)));
        $order->setClientName('Test');
        $order->setClientSurname('User');
        $order->setEmail('test@example.com');
        $order->setPayType(1);
        $order->setLocale('ru');
        $order->setCurrency('EUR');
        $order->setMeasure('m');
        $order->setName('Test Order');
        $order->setCreateDate(new \DateTime());
        $order->setStatus(1);

        $article = new OrderArticle();
        $article->setOrder($order);
        $article->setArticleId(123);
        $article->setAmount('10');
        $article->setPrice('50.5');
        $article->setWeight('1.5');
        $article->setPackagingCount('0');
        $article->setPallet('0');
        $article->setPackaging('0');
        $order->addArticle($article);

        $repository->save($order);
        $orderId = $order->getId();

        // Тестируем эндпоинт
        $client->request('GET', '/api/v1/orders/' . $orderId);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($orderId, $data['id']);
        $this->assertEquals('Test', $data['client_name']);
        $this->assertCount(1, $data['articles']);
        $this->assertEquals(123, $data['articles'][0]['article_id']);
        $this->assertEquals('50.5', $data['articles'][0]['price']);
    }

    public function testGetOrderNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/orders/999999');

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }
}
