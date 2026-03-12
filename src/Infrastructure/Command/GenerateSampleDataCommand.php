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
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

#[AsCommand(
    name: 'app:generate-sample-data',
    description: 'Generate sample orders and simulate traffic to populate metrics'
)]
class GenerateSampleDataCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HttpKernelInterface $kernel
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('orders', 'o', InputOption::VALUE_OPTIONAL, 'Number of orders to generate', 10)
            ->addOption('requests', 'r', InputOption::VALUE_OPTIONAL, 'Number of HTTP requests to simulate', 20)
            ->addOption('with-errors', null, InputOption::VALUE_NONE, 'Simulate errors during requests');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $numOrders = (int) $input->getOption('orders');
        $numRequests = (int) $input->getOption('requests');
        $withErrors = $input->getOption('with-errors');

        $io->title('Generating sample data and traffic...');

        $payType = $this->entityManager->getRepository(PayType::class)->findOneBy([]) ?: new PayType('Credit Card');
        if (!$payType->getId()) {
            $this->entityManager->persist($payType);
        }

        $article = $this->entityManager->getRepository(Article::class)->findOneBy([]) ?: new Article('Sofa', '500.00', '45.00');
        if (!$article->getId()) {
            $this->entityManager->persist($article);
        }

        $this->entityManager->flush();

        $io->section(sprintf('Creating %d orders...', $numOrders));
        for ($i = 0; $i < $numOrders; $i++) {
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
            if (($i + 1) % 10 === 0) {
                $this->entityManager->flush();
            }
        }
        $this->entityManager->flush();
        $io->success(sprintf('%d orders created.', $numOrders));

        $io->section(sprintf('Simulating %d HTTP requests...', $numRequests));
        $routes = [
            ['path' => '/api/v1/orders/stats', 'query' => 'groupBy=day'],
            ['path' => '/api/v1/orders/search', 'query' => 'query=Sample'],
            ['path' => '/api/v1/price', 'query' => 'factory=test&collection=test&article=test'],
            ['path' => '/health', 'query' => ''],
        ];

        for ($i = 0; $i < $numRequests; $i++) {
            $route = $routes[array_rand($routes)];
            $path = $route['path'];
            $query = $route['query'];

            // Simulate some errors if requested
            if ($withErrors && $i % 5 === 0) {
                $path = '/api/v1/non-existent-route';
            }

            $request = Request::create($path . ($query ? '?' . $query : ''));
            $request->server->set('REQUEST_TIME_FLOAT', microtime(true));
            // In CLI, we need to manually trigger the metric collection if it's not happening automatically
            // or ensure the request is properly handled by the kernel.
            try {
                $response = $this->kernel->handle($request);
                // Force a kernel.terminate to ensure MetricsCollectorListener::onKernelTerminate is called
                // which is where many collectors actually flush or process data if they use it.
                if (method_exists($this->kernel, 'terminate')) {
                    $this->kernel->terminate($request, $response);
                }
            } catch (\Throwable $e) {
                // Ignore exceptions during simulation
            }

            if (($i + 1) % 5 === 0) {
                $io->text(sprintf('Processed %d requests...', $i + 1));
            }
            usleep(20000); // 20ms delay
        }
        $io->success(sprintf('%d requests simulated.', $numRequests));

        $io->section('Processing Outbox and Messenger...');
        $outboxCommand = $this->getApplication()->find('app:outbox:process');
        $outboxCommand->run(new ArrayInput([]), $output);

        $messengerCommand = $this->getApplication()->find('messenger:consume');
        $messengerCommand->run(new ArrayInput(['--limit' => 10, '--time-limit' => 5]), $output);

        $io->success('Sample data and metrics populated!');

        return Command::SUCCESS;
    }
}
