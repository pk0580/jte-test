<?php

declare(strict_types=1);

namespace App\Domain\Service\Cache;

interface CacheInvalidatorInterface
{
    public function invalidateStats(): void;
}
