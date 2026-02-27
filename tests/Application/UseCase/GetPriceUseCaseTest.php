<?php

namespace App\Tests\Application\UseCase;

use App\Application\Dto\PriceDto;
use App\Application\UseCase\GetPriceUseCase;
use App\Domain\Service\PriceParserInterface;
use PHPUnit\Framework\TestCase;

class GetPriceUseCaseTest extends TestCase
{
    public function testExecute(): void
    {
        $priceDto = new PriceDto(100.5, 'Factory', 'Collection', 'Article');
        $parser = $this->createMock(PriceParserInterface::class);
        $parser->expects($this->once())
            ->method('parse')
            ->with('Factory', 'Collection', 'Article')
            ->willReturn($priceDto);

        $useCase = new GetPriceUseCase($parser);
        $result = $useCase->execute('Factory', 'Collection', 'Article');

        $this->assertSame($priceDto, $result);
    }
}
