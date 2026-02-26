<?php

namespace App\Tests\Infrastructure\Parser;

use App\Infrastructure\Parser\PriceParser;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class PriceParserTest extends TestCase
{
    public function testParseSuccess(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $response = $this->createStub(ResponseInterface::class);

        $html = '<html><body><meta itemprop="price" content="59.99"></body></html>';

        $response->method('getContent')->willReturn($html);
        $httpClient->method('request')->willReturn($response);

        $parser = new PriceParser($httpClient);
        $dto = $parser->parse('marca-corona', 'arteseta', 'k263-arteseta-camoscio-s000628660');

        $this->assertEquals(59.99, $dto->price);
        $this->assertEquals('marca-corona', $dto->factory);
        $this->assertEquals('arteseta', $dto->collection);
        $this->assertEquals('k263-arteseta-camoscio-s000628660', $dto->article);
    }

    public function testParseJsonPatternSuccess(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $response = $this->createStub(ResponseInterface::class);

        $html = '<html><body><script>var data = {"price":"59,99"};</script></body></html>';

        $response->method('getContent')->willReturn($html);
        $httpClient->method('request')->willReturn($response);

        $parser = new PriceParser($httpClient);
        $dto = $parser->parse('marca-corona', 'arteseta', 'k263-arteseta-camoscio-s000628660');

        $this->assertEquals(59.99, $dto->price);
    }

    public function testParseNoPriceElement(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $response = $this->createStub(ResponseInterface::class);

        $html = '<html><body><div>No price here</div></body></html>';

        $response->method('getContent')->willReturn($html);
        $httpClient->method('request')->willReturn($response);

        $parser = new PriceParser($httpClient);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Could not find price');

        $parser->parse('factory', 'collection', 'article');
    }
}
