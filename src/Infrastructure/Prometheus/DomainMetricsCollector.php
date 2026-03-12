<?php

declare(strict_types=1);

namespace App\Infrastructure\Prometheus;

use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInitTrait;
use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInterface;
use Artprima\PrometheusMetricsBundle\Metrics\PreRequestMetricsCollectorInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Contracts\Cache\CacheInterface;

class DomainMetricsCollector implements MetricsCollectorInterface, PreRequestMetricsCollectorInterface
{
    use MetricsCollectorInitTrait;

    public function __construct(
        private readonly CacheInterface $appCache
    ) {
    }

    public function collectStart(RequestEvent $event): void
    {
        $this->collectionRegistry->getOrRegisterCounter(
            $this->namespace,
            'orders_created_total',
            'Total number of orders created',
            []
        );

        $this->collectionRegistry->getOrRegisterCounter(
            $this->namespace,
            'emails_sent_total',
            'Total number of emails sent',
            []
        );

        try {
            $orders = (int) $this->appCache->get('orders_created_count', fn() => 0);
            $emails = (int) $this->appCache->get('emails_sent_count', fn() => 0);

            if ($orders > 0) {
                $ordersCounter = $this->collectionRegistry->getOrRegisterCounter(
                    $this->namespace,
                    'orders_created_total',
                    'Total number of orders created',
                    []
                );
                $ordersCounter->incBy($orders, []);
                $this->appCache->delete('orders_created_count');
            }
            if ($emails > 0) {
                $emailsCounter = $this->collectionRegistry->getOrRegisterCounter(
                    $this->namespace,
                    'emails_sent_total',
                    'Total number of emails sent',
                    []
                );
                $emailsCounter->incBy($emails, []);
                $this->appCache->delete('emails_sent_count');
            }
        } catch (\Exception $e) {
            error_log('[ERROR] DomainMetricsCollector cache error: ' . $e->getMessage());
        }
    }
}
