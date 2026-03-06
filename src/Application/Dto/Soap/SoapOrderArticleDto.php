<?php

namespace App\Application\Dto\Soap;

use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

class SoapOrderArticleDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
        #[SerializedName('articleId')]
        public int $id = 0,

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
