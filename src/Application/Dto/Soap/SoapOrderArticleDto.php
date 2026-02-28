<?php

namespace App\Application\Dto\Soap;

use Symfony\Component\Validator\Constraints as Assert;

class SoapOrderArticleDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
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
