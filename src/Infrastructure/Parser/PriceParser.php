<?php

namespace App\Infrastructure\Parser;

use App\Application\Dto\PriceDto;
use App\Domain\Service\PriceParserInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use RuntimeException;

class PriceParser implements PriceParserInterface
{
    private const BASE_URL = 'https://tile.expert';

    public function __construct(
        private readonly HttpClientInterface $httpClient
    ) {}

    public function parse(string $factory, string $collection, string $article): PriceDto
    {
        $url = sprintf('%s/it/tile/%s/%s/a/%s', self::BASE_URL, $factory, $collection, $article);

        try {
            $response = $this->httpClient->request('GET', $url);
            $content = $response->getContent();
        } catch (\Exception $e) {
            throw new RuntimeException("Failed to fetch price from $url: " . $e->getMessage());
        }

        $crawler = new Crawler($content);

        // На сайте tile.expert цена часто находится в атрибутах data-price или внутри скриптов.
        // Попробуем найти цену через селекторы, которые часто встречаются на этом сайте.
        // Например, .price-val или через мета-теги.
        $price = null;

        // Попробуем найти в meta itemprop="price"
        $metaPrice = $crawler->filter('meta[itemprop="price"]');
        if ($metaPrice->count() > 0) {
            $price = (float)$metaPrice->attr('content');
        }

        // Если не нашли, попробуем поискать в тексте через регулярное выражение,
        // специфичное для формата "price":"59,99"
        if ($price === null) {
            if (preg_match('/"price":"(\d+[.,]\d+)"/', $content, $matches)) {
                $price = (float) str_replace(',', '.', $matches[1]);
            }
        }

        // Еще один вариант - поиск элемента с классом price-val или подобным
        if ($price === null) {
            $priceElement = $crawler->filter('.price-val, .price, [data-price]');
            if ($priceElement->count() > 0) {
                $priceText = $priceElement->first()->text();
                preg_match('/(\d+[.,]\d+)/', $priceText, $matches);
                if (isset($matches[1])) {
                    $price = (float) str_replace(',', '.', $matches[1]);
                }
            }
        }

        if ($price === null) {
            throw new RuntimeException("Could not find price on page $url");
        }

        return new PriceDto(
            price: $price,
            factory: $factory,
            collection: $collection,
            article: $article
        );
    }
}
