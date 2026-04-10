<?php

declare(strict_types=1);

namespace App\Domain\Service\Metrics;

interface MetricsServiceInterface
{
    /**
     * @param array<string, string> $labels
     */
    public function incrementOrdersCreated(array $labels = []): void;
}
