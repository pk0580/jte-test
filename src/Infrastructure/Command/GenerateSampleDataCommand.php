<?php

declare(strict_types=1);

namespace App\Infrastructure\Command;

use App\Domain\Entity\Article;
use App\Domain\Entity\Order;
use App\Domain\Entity\OrderArticle;
use App\Domain\Entity\PayType;
use App\Domain\ValueObject\CustomerInfo;
use App\Domain\ValueObject\DeliveryAddress;
use App\Domain\ValueObject\DeliveryConfig;
use App\Domain\ValueObject\DeliveryTerms;
use App\Domain\ValueObject\FinancialTerms;
use App\Domain\ValueObject\ManagerInfo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-sample-data',
    description: 'Generate sample orders to populate metrics'
)]
class GenerateSampleDataCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Generating sample data...');

        $payType = $this->entityManager->getRepository(PayType::class)->findOneBy([]) ?: new PayType('Credit Card');
        if (!$payType->getId()) {
            $this->entityManager->persist($payType);
        }

        $article = $this->entityManager->getRepository(Article::class)->findOneBy([]) ?: new Article('Sofa', '500.00', '45.00');
        if (!$article->getId()) {
            $this->entityManager->persist($article);
        }

        $this->entityManager->flush();

        for ($i = 0; $i < 5; $i++) {
            $order = new Order(
                payType: $payType,
                name: 'Sample Order ' . uniqid(),
                customerInfo: new CustomerInfo('John', 'Doe', 'john.doe@example.com'),
                deliveryAddress: new DeliveryAddress(city: 'New York', address: 'Broadway 123'),
                deliveryTerms: new DeliveryTerms(),
                managerInfo: new ManagerInfo(),
                financialTerms: new FinancialTerms(),
                deliveryConfig: new DeliveryConfig(),
                description: 'Generated for metrics visualization'
            );

            $orderArticle = new OrderArticle(
                $order,
                $article,
                '1.000',
                '500.00',
                '45.000',
                '1',
                '0',
                '0',
                'pcs'
            );
            $order->addArticle($orderArticle);

            $this->entityManager->persist($order);
            $io->text(sprintf('Order created: %s', $order->getName()));

            // Artificial delay to make metrics more visible in time series
            usleep(100000);
        }

        $this->entityManager->flush();
        $io->success('Sample data generated successfully!');

        return Command::SUCCESS;
    }
}
