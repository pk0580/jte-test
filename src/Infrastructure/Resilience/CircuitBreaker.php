<?php

declare(strict_types=1);

namespace App\Infrastructure\Resilience;

use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Throwable;

/**
 * Реализация паттерна Circuit Breaker для защиты от сбоев внешних систем.
 */
class CircuitBreaker
{
    private const string STATE_KEY_PREFIX = 'cb_state_';
    private const string FAILURES_KEY_PREFIX = 'cb_failures_';

    private const string STATE_CLOSED = 'closed';
    private const string STATE_OPEN = 'open';
    private const string STATE_HALF_OPEN = 'half_open';

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly string $name,
        private readonly int $failureThreshold = 3,
        private readonly int $recoveryTime = 60
    ) {
    }

    /**
     * @template T
     * @param callable(): T $operation
     * @return T
     * @throws Throwable
     */
    public function call(callable $operation): mixed
    {
        if ($this->isOpen()) {
            throw new \RuntimeException("Circuit breaker '{$this->name}' is open");
        }

        try {
            $result = $operation();
            $this->onSuccess();

            return $result;
        } catch (Throwable $e) {
            $this->onFailure();
            throw $e;
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function isOpen(): bool
    {
        $state = $this->cache->get($this->getStateKey(), function (ItemInterface $item) {
            return self::STATE_CLOSED;
        });

        return $state === self::STATE_OPEN;
    }

    private function onFailure(): void
    {
        $failuresKey = $this->getFailuresKey();

        // Получаем текущее количество ошибок и увеличиваем его
        $failures = (int)$this->cache->get($failuresKey, function (ItemInterface $item) {
            return 0;
        }) + 1;

        // Пересохраняем с обновленным счетчиком и временем жизни
        $this->cache->delete($failuresKey);
        $this->cache->get($failuresKey, function (ItemInterface $item) use ($failures) {
            $item->expiresAfter($this->recoveryTime);
            $item->set($failures);
            return $failures;
        });

        if ($failures >= $this->failureThreshold) {
            $stateKey = $this->getStateKey();
            $this->cache->delete($stateKey);
            $this->cache->get($stateKey, function (ItemInterface $item) {
                $item->expiresAfter($this->recoveryTime);
                return self::STATE_OPEN;
            });
        }
    }

    private function onSuccess(): void
    {
        $this->cache->delete($this->getFailuresKey());
        $this->cache->delete($this->getStateKey());
    }

    private function getStateKey(): string
    {
        return self::STATE_KEY_PREFIX . $this->name;
    }

    private function getFailuresKey(): string
    {
        return self::FAILURES_KEY_PREFIX . $this->name;
    }
}
