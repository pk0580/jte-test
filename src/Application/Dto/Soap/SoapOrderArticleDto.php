<?php

namespace App\Application\Dto\Soap;

use App\Application\Validator\Constraints as AppAssert;
use App\Domain\Entity\Article;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

class SoapOrderArticleDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
        #[AppAssert\EntityExists(entity: Article::class)]
        public int $articleId = 0,

        #[Assert\NotBlank]
        #[Assert\Positive]
        public string $amount = '0',

        #[Assert\NotBlank]
        #[Assert\Positive]
        public string $price = '0',

        #[Assert\NotBlank]
        #[Assert\Positive]
        public string $weight = '0'
    ) {}
}
