<?php

namespace App\Tests\Controller\Api\v1;

use App\Domain\Entity\Order;
use App\Domain\Entity\OrderArticle;
use App\Domain\Repository\OrderRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OrderControllerTest extends WebTestCase
{
    public function testGetStats(): void
    {
        $client = static::createClient();

        // В идеале здесь нужно загрузить фикстуры, но так как мы работаем с существующей БД (dump.sql),
        // мы можем просто проверить, что эндпоинт доступен и возвращает правильную структуру.
        // Если БД пустая, то результат будет с пустым массивом items.

        $client->request('GET', '/api/v1/orders/stats', [
            'group_by' => 'month',
            'page' => 1,
            'limit' => 10
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('items', $responseData);
        $this->assertArrayHasKey('meta', $responseData);
        $this->assertArrayHasKey('total_items', $responseData['meta']);
        $this->assertArrayHasKey('page', $responseData['meta']);
        $this->assertArrayHasKey('limit', $responseData['meta']);
        $this->assertArrayHasKey('total_pages', $responseData['meta']);

        if (count($responseData['items']) > 0) {
            $item = $responseData['items'][0];
            $this->assertArrayHasKey('period', $item);
            $this->assertArrayHasKey('order_count', $item);
            $this->assertArrayHasKey('total_amount', $item);
            // Для 'month' формат должен быть YYYY-MM
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}$/', $item['period']);
        }
    }

    public function testGetStatsInvalidGroupBy(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/orders/stats', [
            'group_by' => 'invalid'
        ]);

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Invalid group_by parameter. Allowed: day, month, year', $responseData['error']);
    }

    public function testGetStatsInvalidPage(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/orders/stats', [
            'page' => 0
        ]);

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Page must be greater than or equal to 1', $responseData['error']);
    }

    public function testGetStatsInvalidLimit(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/orders/stats', [
            'limit' => 101
        ]);

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Limit must be between 1 and 100', $responseData['error']);
    }
}
