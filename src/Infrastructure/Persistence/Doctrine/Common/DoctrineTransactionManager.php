<?php

namespace App\Infrastructure\Persistence\Doctrine\Common;

use App\Application\Common\TransactionManagerInterface;
use Doctrine\ORM\EntityManagerInterface;

readonly class DoctrineTransactionManager implements TransactionManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function wrapInTransaction(callable $callback): mixed
    {
        return $this->entityManager->wrapInTransaction($callback);
    }
}
