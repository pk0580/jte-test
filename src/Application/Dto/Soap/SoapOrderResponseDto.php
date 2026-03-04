<?php

namespace App\Application\Dto\Soap;

readonly class SoapOrderResponseDto
{
    public function __construct(
        public bool $success,
        public ?int $orderId = null,
        public ?string $message = null
    ) {}
}
