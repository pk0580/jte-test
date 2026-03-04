<?php

namespace App\Application\Dto\Soap;

use Symfony\Component\Serializer\Annotation\SerializedName;

readonly class SoapOrderResponseDto
{
    public function __construct(
        #[SerializedName('success')]
        public bool $success,

        #[SerializedName('orderId')]
        public ?int $orderId = null,

        #[SerializedName('message')]
        public ?string $message = null
    ) {}
}
