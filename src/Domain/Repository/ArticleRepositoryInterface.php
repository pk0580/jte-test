<?php

namespace App\Domain\Repository;

use App\Domain\Entity\Article;

interface ArticleRepositoryInterface
{
    public function findById(int $id): ?Article;
}
