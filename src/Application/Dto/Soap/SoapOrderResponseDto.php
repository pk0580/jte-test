<?php

namespace App\Application\Dto\Soap;

readonly class SoapOrderResponseDto
{
    public function __construct(
        public bool $success,
        public ?int $order_id = null,
        public ?string $message = null
    ) {}
}
