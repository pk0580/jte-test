<?php

namespace App\Application\Dto\Soap;

use App\Application\Validator\Constraints as AppAssert;
use App\Domain\Entity\PayType;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

class CreateOrderSoapRequestDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $clientName = '',

        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $clientSurname = '',

        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 150)]
        public string $email = '',

        #[Assert\NotBlank]
        #[Assert\Positive]
        #[AppAssert\BatchEntityExists(entity: PayType::class, message: 'Invalid payment type.')]
        public int $payType = 0,

        /** @var array<int, SoapOrderArticleDto> */
        #[Assert\NotBlank]
        #[Assert\Valid]
        #[AppAssert\BatchEntityExists(entity: \App\Domain\Entity\Article::class, fields: ['articleId'])]
        public array $articles = []
    ) {}
}
