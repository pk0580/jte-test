<?php

declare(strict_types=1);

namespace App\Domain\Exception;

class ArticleNotFoundException extends \DomainException
{
    public static function forArticleId(int $articleId): self
    {
        return new self(sprintf('Article with ID %d not found in order.', $articleId));
    }
}
