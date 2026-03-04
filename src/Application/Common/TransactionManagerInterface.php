<?php

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
