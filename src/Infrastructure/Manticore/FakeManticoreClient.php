<?php

declare(strict_types=1);

namespace App\Infrastructure\Manticore;

class FakeManticoreClient
{
    public function search(string $query): array
    {
        return [];
    }
}
