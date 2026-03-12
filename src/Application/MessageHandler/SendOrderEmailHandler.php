<?php

namespace App\Application\MessageHandler;

use App\Application\Message\SendOrderEmailMessage;
use App\Domain\Repository\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Cache\CacheInterface;

#[AsMessageHandler]
readonly class SendOrderEmailHandler
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private LoggerInterface $logger,
        private CacheInterface $appCache
    ) {}

    public function __invoke(SendOrderEmailMessage $message): void
    {
        $order = $this->orderRepository->findById($message->getOrderId());
        if (!$order) {
            $this->logger->error(sprintf('Order #%d not found for email notification', $message->getOrderId()));
            return;
        }

        // Имитация формирования данных для письма вне транзакции БД
        $recipientEmail = $order->getCustomerInfo()->getEmail();
        $subject = sprintf('Order #%d confirmed', $order->getId());
        $template = 'order_confirmation.html.twig';

        // Имитация отправки email
        $this->logger->info(sprintf(
            'Sending email to %s for order #%d: %s (template: %s)',
            $recipientEmail,
            $order->getId(),
            $subject,
            $template
        ));

        // Здесь должна быть реальная логика через MailerInterface

        try {
            $name = 'emails_sent_count';
            $val = (int)$this->appCache->get($name, fn() => 0);
            $this->appCache->delete($name);
            $this->appCache->get($name, fn() => $val + 1);
        } catch (\Exception) {
        }
    }
}
