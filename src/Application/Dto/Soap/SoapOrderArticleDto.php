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
        #[SerializedName('articleId')]
        public int $articleId,

        #[Assert\NotBlank]
        #[Assert\Positive]
        #[SerializedName('amount')]
        public string $amount,

        #[Assert\NotBlank]
        #[Assert\Positive]
        #[SerializedName('price')]
        public string $price,

        #[Assert\NotBlank]
        #[Assert\Positive]
        #[SerializedName('weight')]
        public string $weight
    ) {}
}
