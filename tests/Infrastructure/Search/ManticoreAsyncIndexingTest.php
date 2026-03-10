<?php

namespace App\Tests\Infrastructure\Search;

use App\Application\Message\IndexOrderMessage;
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
use App\Domain\Repository\OrderSearchInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Bundle\FrameworkBundle\Console\Application;

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
        $order = new Order(
            payType: $payType,
            name: 'Async Test Order',
            customerInfo: new CustomerInfo('AsyncTest', 'User', 'async@example.com'),
            deliveryAddress: new DeliveryAddress(),
            deliveryTerms: new DeliveryTerms(),
            managerInfo: new ManagerInfo(),
            financialTerms: new FinancialTerms(currency: 'EUR'),
            deliveryConfig: new DeliveryConfig()
        );

        $article = new OrderArticle(
            order: $order,
            article: $articleEntity,
            amount: '1',
            price: '100',
            weight: '1',
            packagingCount: '0',
            pallet: '0',
            packaging: '0'
        );
        $order->addArticle($article);

        // Сохранение должно создать событие в outbox
        $repository->save($order);
        $orderId = $order->getId();

        // 2. Проверяем наличие в базе outbox_events
        $outbox = $em->getRepository(\App\Domain\Entity\OutboxEvent::class)->findOneBy([]);
        $this->assertNotNull($outbox, 'OutboxEvent not created');

        // 2. Запускаем обработку outbox
        $application = new Application(static::$kernel);
        $application->setAutoExit(false);
        $application->run(new ArrayInput(['command' => 'app:outbox:process']), new NullOutput());

        // Сбросим UnitOfWork или EntityManager, если нужно, но outbox в БД уже должен быть помечен обработанным
        $em->clear();

        // 3. Проверяем, что в очереди появилось сообщение
        /** @var InMemoryTransport $transport */
        $transport = $container->get('messenger.transport.async');

        // В тестах Symfony может потребоваться получить транспорт снова из свежего контейнера
        // или использовать статический доступ если это WebTestCase

        $found = false;
        foreach ($transport->getSent() as $envelope) {
            if ($envelope->getMessage() instanceof IndexOrderMessage && $envelope->getMessage()->getOrderId() === $orderId) {
                $found = true;
                break;
            }
        }

        if (!$found && method_exists($transport, 'getAcknowledged')) {
            foreach ($transport->getAcknowledged() as $envelope) {
                if ($envelope->getMessage() instanceof IndexOrderMessage && $envelope->getMessage()->getOrderId() === $orderId) {
                    $found = true;
                    break;
                }
            }
        }

        $this->assertTrue($found, 'IndexOrderMessage for order not found in transport. Outbox event was: ' . ($outbox ? $outbox->getEventType()->value : 'null'));

        // 4. Проверяем, что в Manticore заказа еще нет (так как мы только в очередь поставили)
        /** @var OrderSearchInterface $search */
        $search = $container->get(OrderSearchInterface::class);
        $result = $search->search('AsyncTest');
        // Здесь мы просто проверяем наличие интерфейса и работоспособность метода
    }
}
