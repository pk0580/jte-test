<?php

namespace App\Domain\Dto\Search;

readonly class SearchOrderDto
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
    ) {}
}
