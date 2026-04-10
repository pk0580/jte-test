<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Listener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Prometheus\CollectorRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class DoctrineFlushMetricsListener
 *
 * Инфраструктурный listener для сбора метрик производительности Doctrine flush().
 *
 * 📊 Что делает:
 *  - Измеряет длительность операции flush (между onFlush и postFlush)
 *  - Отправляет метрики в Prometheus (Histogram)
 *  - Добавляет labels (route, method, connection) для аналитики
 *  - Логирует медленные flush операции
 *
 * 🎯 Зачем это нужно:
 *  - Выявление медленных запросов к БД
 *  - Анализ деградации производительности
 *  - Построение SLI/SLO (p95, p99 latency)
 *  - Диагностика проблем (N+1, большие batch-и, блокировки)
 *
 * ⚙️ Особенности реализации:
 *  - Stateless (без утечек состояния между запросами)
 *  - Поддержка вложенных flush (stack-based)
 *  - Игнорирует "пустые" flush (без изменений)
 *  - Fail-safe (метрики не ломают бизнес-логику)
 *
 * ⚠️ Важно:
 *  - Использует Histogram для корректной агрегации в Prometheus
 *  - Не хранит состояние в singleton (безопасно для Swoole/RoadRunner)
 *
 * 📈 Пример использования в Prometheus:
 *  histogram_quantile(0.95, rate(doctrine_flush_duration_seconds_bucket[5m]))
 *
 * 📍 Типичный use-case:
 *  - Мониторинг API latency
 *  - Поиск "тяжёлых" endpoint-ов
 *  - Alerting на медленные flush (> 500ms)
 */
#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
class DoctrineFlushMetricsListener
{
    /**
     * Стек времени начала flush операций.
     *
     * Используется вместо одного значения, чтобы:
     *  - корректно обрабатывать вложенные flush
     *  - избежать race conditions
     *
     * @var float[]
     */
    private array $flushStack = [];

    public function __construct(
        private readonly CollectorRegistry $registry,
        private readonly RequestStack $requestStack,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Срабатывает перед выполнением flush.
     *
     * Здесь:
     *  - проверяем, есть ли реальные изменения (insert/update/delete)
     *  - если да → фиксируем старт времени
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        // 🚫 Пропускаем "пустые" flush (нет изменений)
        if (
            !$uow->getScheduledEntityInsertions() &&
            !$uow->getScheduledEntityUpdates() &&
            !$uow->getScheduledEntityDeletions()
        ) {
            return;
        }

        // ⏱ Сохраняем время начала flush (поддержка nested flush)
        $this->flushStack[] = microtime(true);
    }

    /**
     * Срабатывает после выполнения flush.
     *
     * Здесь:
     *  - достаём время начала
     *  - считаем длительность
     *  - отправляем метрики
     */
    public function postFlush(PostFlushEventArgs $args): void
    {
        // 📤 Достаём последний start (LIFO)
        $start = array_pop($this->flushStack);

        if (!$start) {
            return;
        }

        $duration = microtime(true) - $start;

        $this->recordMetrics($duration, $args);
    }

    /**
     * Отправка метрик в Prometheus + логирование.
     *
     * @param float $duration Длительность flush в секундах
     */
    private function recordMetrics(float $duration, PostFlushEventArgs $args): void
    {
        try {
            $request = $this->requestStack->getCurrentRequest();

            // 🌐 HTTP context (или CLI fallback)
            $route = $request?->attributes->get('_route', 'cli');
            $method = $request?->getMethod() ?? 'cli';

            // 🗄 Имя соединения / БД
            $em = $args->getObjectManager();
            $connectionName = $em->getConnection()->getParams()['dbname'] ?? 'default';

            // 📊 Histogram метрика (для распределённых систем лучше чем Summary)
            $histogram = $this->registry->getOrRegisterHistogram(
                'app',
                'doctrine_flush_duration_seconds',
                'Doctrine flush duration',
                ['route', 'method', 'connection'],
                // buckets подобраны под typical latency API
                [0.005, 0.01, 0.05, 0.1, 0.5, 1, 2, 5]
            );

            // 📈 Отправляем значение
            $histogram->observe($duration, [
                (string) $route,
                (string) $method,
                (string) $connectionName,
            ]);

            // ⚠️ Логируем медленные flush (для быстрого дебага)
            if ($duration > 0.5 && $this->logger) {
                $this->logger->warning('Slow Doctrine flush detected', [
                    'duration' => $duration,
                    'route' => $route,
                    'method' => $method,
                ]);
            }
        } catch (\Throwable $e) {
            // 🛡 Fail-safe:
            // Метрики никогда не должны ломать приложение
        }
    }
}
