<?php

namespace App\Tests\Infrastructure\Search;

use App\Infrastructure\Search\OrderSearchQueryBuilder;
use PHPUnit\Framework\TestCase;

class OrderSearchQueryBuilderTest extends TestCase
{
    private OrderSearchQueryBuilder $queryBuilder;

    protected function setUp(): void
    {
        $this->queryBuilder = new OrderSearchQueryBuilder();
    }

    public function testOffsetPagination(): void
    {
        $dto = $this->queryBuilder->build('test', 2, 10, null, null);

        $this->assertEquals(10, $dto->offset);
        $this->assertEquals(10, $dto->limit);
        $this->assertNull($dto->lastId);
        $this->assertEmpty($dto->sort);
    }

    public function testCursorPagination(): void
    {
        $dto = $this->queryBuilder->build('test', 2, 10, 123, null);

        $this->assertEquals(0, $dto->offset); // Offset must be 0 for cursor pagination
        $this->assertEquals(10, $dto->limit);
        $this->assertEquals(123, $dto->lastId);
        $this->assertEquals(['id' => 'desc'], $dto->sort);
    }

    public function testWeightedQueryGeneration(): void
    {
        $dto = $this->queryBuilder->build('simple', 1, 10);
        $this->assertStringContainsString('@number ^5', $dto->query);
        $this->assertStringContainsString('simple', $dto->query);

        $dtoComplex = $this->queryBuilder->build('@custom query', 1, 10);
        $this->assertEquals('@custom query', $dtoComplex->query);
    }
}
