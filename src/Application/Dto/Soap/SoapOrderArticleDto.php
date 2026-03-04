<?php

namespace App\Application\Dto\Soap;

use App\Application\Validator\Constraints as AppAssert;
use App\Domain\Entity\Article;
use Symfony\Component\Validator\Constraints as Assert;

class SoapOrderArticleDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
        #[AppAssert\EntityExists(entity: Article::class)]
        public int $article_id,

        #[Assert\NotBlank]
        #[Assert\Positive]
        public string $amount,

        #[Assert\NotBlank]
        #[Assert\Positive]
        public string $price,

        #[Assert\NotBlank]
        #[Assert\Positive]
        public string $weight
    ) {}
}
