<?php

declare(strict_types=1);

namespace App\Infrastructure\Prometheus;

use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInitTrait;
use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInterface;
use Artprima\PrometheusMetricsBundle\Metrics\PreRequestMetricsCollectorInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class SystemMetricsCollector implements MetricsCollectorInterface, PreRequestMetricsCollectorInterface
{
    use MetricsCollectorInitTrait;

    public function collectStart(RequestEvent $event): void
    {
        $cpuGauge = $this->collectionRegistry->getOrRegisterGauge(
            $this->namespace,
            'node_cpu_seconds_total',
            'Seconds the CPUs spent in each mode',
            ['cpu', 'mode']
        );

        $memGauge = $this->collectionRegistry->getOrRegisterGauge(
            $this->namespace,
            'node_memory_available_bytes',
            'Memory information field available_bytes',
            []
        );

        $this->collectCpuMetrics($cpuGauge);
        $this->collectMemoryMetrics($memGauge);
    }

    private function collectCpuMetrics($gauge): void
    {
        if (!is_readable('/proc/stat')) {
            return;
        }

        $stats = file('/proc/stat');
        if ($stats === false) {
            return;
        }

        foreach ($stats as $line) {
            if (preg_match('/^cpu(?<cpu>\d+) (?<user>\d+) (?<nice>\d+) (?<system>\d+) (?<idle>\d+) (?<iowait>\d+) (?<irq>\d+) (?<softirq>\d+) (?<steal>\d+) (?<guest>\d+) (?<guest_nice>\d+)/', $line, $matches)) {
                $cpu = $matches['cpu'];
                // Нам нужны секунды. В /proc/stat значения в USER_HZ (обычно 1/100 секунды)
                $userHz = 100;
                $gauge->set((float)$matches['user'] / $userHz, [$cpu, 'user']);
                $gauge->set((float)$matches['system'] / $userHz, [$cpu, 'system']);
                $gauge->set((float)$matches['idle'] / $userHz, [$cpu, 'idle']);
            }
        }
    }

    private function collectMemoryMetrics($gauge): void
    {
        if (!is_readable('/proc/meminfo')) {
            return;
        }

        $meminfo = file_get_contents('/proc/meminfo');
        if ($meminfo === false) {
            return;
        }

        if (preg_match('/MemAvailable:\s+(\d+)\s+kB/', $meminfo, $matches)) {
            $gauge->set((float)$matches[1] * 1024, []);
        } elseif (preg_match('/MemFree:\s+(\d+)\s+kB/', $meminfo, $matches)) {
            // Fallback if MemAvailable is not present
            $gauge->set((float)$matches[1] * 1024, []);
        }
    }
}
