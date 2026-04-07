<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Article;

interface ArticleRepositoryInterface
{
    public function findById(int $id): ?Article;
}
