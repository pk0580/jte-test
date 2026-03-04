<?php

namespace App\Tests\Controller;

use App\Domain\Entity\Article;
use App\Domain\Entity\Order;
use App\Domain\Entity\OrderArticle;
use App\Domain\Entity\PayType;
use App\Domain\ValueObject\CustomerInfo;
use App\Domain\ValueObject\FinancialTerms;
use App\Domain\ValueObject\DeliveryConfig;
use App\Domain\ValueObject\DeliveryAddress;
use App\Domain\ValueObject\DeliveryTerms;
use App\Domain\ValueObject\ManagerInfo;
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

        // Создаем тестовые зависимости
        $em = $container->get('doctrine')->getManager();
        $payType = new PayType('Test Pay');
        $em->persist($payType);
        $articleEntity = new Article('Test Article', '50.5', '1.5');
        $em->persist($articleEntity);
        $em->flush();

        // Создаем тестовый заказ
        $order = new Order(
            payType: $payType,
            name: 'Test Order',
            customerInfo: new CustomerInfo('Test', 'User', 'test@example.com'),
            financialTerms: new FinancialTerms(currency: 'EUR')
        );

        $article = new OrderArticle();
        $article->setOrder($order);
        $article->setArticle($articleEntity);
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
        $this->assertEquals($articleEntity->getId(), $data['articles'][0]['article_id']);
        $this->assertEquals('50.5', $data['articles'][0]['price']);
    }

    public function testGetOrderNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/orders/999999');

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }
}
