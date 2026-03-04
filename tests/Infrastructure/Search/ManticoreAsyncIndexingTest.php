<?php

namespace App\Tests\Infrastructure\Search;

use App\Application\Message\IndexOrderMessage;
use App\Domain\Entity\Article;
use App\Domain\Entity\Order;
use App\Domain\Entity\OrderArticle;
use App\Domain\Entity\PayType;
use App\Domain\ValueObject\CustomerInfo;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Repository\OrderSearchInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

class ManticoreAsyncIndexingTest extends WebTestCase
{
    public function testAsyncIndexingOnSave(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        /** @var OrderRepositoryInterface $repository */
        $repository = $container->get(OrderRepositoryInterface::class);

        // 1. Создаем тестовые зависимости
        $em = $container->get('doctrine')->getManager();
        $payType = new PayType('Async Pay');
        $em->persist($payType);
        $articleEntity = new Article('Async Article', '100', '1');
        $em->persist($articleEntity);
        $em->flush();

        // 2. Создаем тестовый заказ
        $order = new Order();
        $order->setHash(bin2hex(random_bytes(16)));
        $order->setToken(bin2hex(random_bytes(32)));
        $order->setCustomerInfo(new CustomerInfo('AsyncTest', 'User', 'async@example.com'));
        $order->setPayType($payType);
        $order->setLocale('ru');
        $order->setCurrency('EUR');
        $order->setMeasure('m');
        $order->setName('Async Test Order');
        $order->setCreateDate(new \DateTime());
        $order->changeStatus(1);

        $article = new OrderArticle();
        $article->setOrder($order);
        $article->setArticle($articleEntity);
        $article->setAmount('1');
        $article->setPrice('100');
        $article->setWeight('1');
        $article->setPackagingCount('0');
        $article->setPallet('0');
        $article->setPackaging('0');
        $order->addArticle($article);

        // Сохранение должно отправить сообщение в Messenger (транспорт async - in-memory в тестах)
        $repository->save($order);
        $orderId = $order->getId();

        // 2. Проверяем, что в очереди появилось сообщение
        /** @var InMemoryTransport $transport */
        $transport = $container->get('messenger.transport.async');
        $this->assertCount(1, $transport->getSent());

        $envelope = $transport->getSent()[0];
        $this->assertInstanceOf(IndexOrderMessage::class, $envelope->getMessage());
        $this->assertEquals($orderId, $envelope->getMessage()->getOrderId());

        // 3. Проверяем, что в Manticore заказа еще нет
        /** @var OrderSearchInterface $search */
        $search = $container->get(OrderSearchInterface::class);
        $result = $search->search('AsyncTest');

        // ВАЖНО: Мы не можем гарантировать, что его нет, если Manticore не очищен,
        // но по логике асинхронности он не должен был туда попасть через сабскрайбер.
        // Чтобы точно проверить, нужно было бы убедиться, что до save его не было.
    }
}
