<?php

namespace App\Infrastructure\Persistence\Doctrine\Repository\Decorator;

use App\Domain\Entity\Article;
use App\Domain\Repository\ArticleRepositoryInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class ArticleRepositoryCacheDecorator implements ArticleRepositoryInterface
{
    public function __construct(
        private readonly ArticleRepositoryInterface $inner,
        private readonly CacheInterface $referenceCache
    ) {}

    public function findById(int $id): ?Article
    {
        return $this->referenceCache->get(sprintf('article_%d', $id), function (ItemInterface $item) use ($id) {
            $item->expiresAfter(3600);
            return $this->inner->findById($id);
        });
    }
}
