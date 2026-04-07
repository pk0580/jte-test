<?php

declare(strict_types=1);

namespace App\Application\Dto\Search;

class SearchOrderResponseDto
{
    public function __construct(
        public int $id,
        public string $number,
        public string $email,
        public string $clientName,
        public string $clientSurname,
        public string $companyName,
        public string $description,
        public int $status,
    ) {
    }
}
