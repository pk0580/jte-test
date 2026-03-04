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
        #[SerializedName('clientName')]
        public string $clientName,

        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        #[SerializedName('clientSurname')]
        public string $clientSurname,

        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 150)]
        #[SerializedName('email')]
        public string $email,

        #[Assert\NotBlank]
        #[Assert\Positive]
        #[AppAssert\EntityExists(entity: PayType::class, message: 'Invalid payment type.')]
        #[SerializedName('payType')]
        public int $payType,

        /** @var SoapOrderArticleDto[] */
        #[Assert\NotBlank]
        #[Assert\Valid]
        #[SerializedName('articles')]
        public array $articles = []
    ) {}
}
