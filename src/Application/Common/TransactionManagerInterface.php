<?php

declare(strict_types=1);

namespace App\Application\Common;

interface TransactionManagerInterface
{
    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function wrapInTransaction(callable $callback): mixed;
}
