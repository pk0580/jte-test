<?php

declare(strict_types=1);

namespace App\Tests\Application\Soap;

use App\Application\Dto\Soap\SoapOrderResponseDto;
use App\Application\Soap\SoapConverter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SoapConverterTest extends TestCase
{
    public function testNormalizeResponseWithNulls(): void
    {
        $normalizer = $this->createMock(NormalizerInterface::class);
        $denormalizer = $this->createMock(DenormalizerInterface::class);

        $normalizer->method('normalize')->willReturn([
            'success' => true,
            'orderId' => null,
            'message' => null,
        ]);

        $converter = new SoapConverter($normalizer, $denormalizer);
        $responseDto = new SoapOrderResponseDto(true);

        $result = $converter->normalizeResponse($responseDto);

        $this->assertEquals(0, $result['orderId']);
        $this->assertEquals('', $result['message']);
    }

    public function testNormalizeResponseWithValues(): void
    {
        $normalizer = $this->createMock(NormalizerInterface::class);
        $denormalizer = $this->createMock(DenormalizerInterface::class);

        $normalizer->method('normalize')->willReturn([
            'success' => true,
            'orderId' => 123,
            'message' => 'Success',
        ]);

        $converter = new SoapConverter($normalizer, $denormalizer);
        $responseDto = new SoapOrderResponseDto(true, 123, 'Success');

        $result = $converter->normalizeResponse($responseDto);

        $this->assertEquals(123, $result['orderId']);
        $this->assertEquals('Success', $result['message']);
    }
}
