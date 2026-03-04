<?php

namespace App\Domain\Repository;

use App\Domain\Entity\PayType;

interface PayTypeRepositoryInterface
{
    public function findById(int $id): ?PayType;
}
