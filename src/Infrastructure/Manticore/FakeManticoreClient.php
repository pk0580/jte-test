<?php

namespace App\Infrastructure\Manticore;

class FakeManticoreClient
{
    public function search(string $query): array
    {
        return [];
    }
}
