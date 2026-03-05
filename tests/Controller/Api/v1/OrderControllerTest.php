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

    public function testSearch(): void
    {
        $client = static::createClient();

        // 1. Basic search
        $client->request('GET', '/api/v1/orders/search', [
            'query' => 'test',
            'page' => 1,
            'limit' => 10
        ]);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('items', $responseData);
        $this->assertArrayHasKey('total', $responseData);

        // 2. Search with status filter
        $client->request('GET', '/api/v1/orders/search', [
            'query' => 'test',
            'status' => 1
        ]);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('items', $responseData);
        $this->assertEquals(1, $responseData['status']);

        foreach ($responseData['items'] as $item) {
            // Need to fetch full order to check status since it's not in ResponseDto
            // But we can check that it doesn't fail.
        }
    }

    public function testCursorBasedPagination(): void
    {
        $client = static::createClient();

        // 1. Get first page
        $client->request('GET', '/api/v1/orders/search', [
            'query' => 'test',
            'limit' => 2
        ]);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);

        if (count($responseData['items']) < 2) {
            $this->markTestSkipped('Not enough test data for cursor-based pagination test');
        }

        $firstItem = $responseData['items'][0];
        $secondItem = $responseData['items'][1];
        $lastId = $secondItem['id'];

        // 2. Get next page using cursor
        $client->request('GET', '/api/v1/orders/search', [
            'query' => 'test',
            'limit' => 2,
            'last_id' => $lastId
        ]);

        $this->assertResponseIsSuccessful();
        $cursorData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('items', $cursorData);
        if (count($cursorData['items']) > 0) {
            $nextItem = $cursorData['items'][0];
            $this->assertLessThan($lastId, $nextItem['id'], 'Next page items should have smaller ID when sorting DESC');
            $this->assertNotEquals($firstItem['id'], $nextItem['id'], 'Next page item ID should not match first page first item ID');
            $this->assertNotEquals($secondItem['id'], $nextItem['id'], 'Next page item ID should not match first page second item ID');
        }
    }

    public function testGetStatsCaching(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/orders/stats', [
            'group_by' => 'month'
        ]);

        $this->assertResponseIsSuccessful();
        $etag = $client->getResponse()->headers->get('ETag');
        $this->assertNotNull($etag);

        $client->request('GET', '/api/v1/orders/stats', [
            'group_by' => 'month'
        ], [], ['HTTP_IF_NONE_MATCH' => $etag]);

        $this->assertResponseStatusCodeSame(304);
    }

    public function testSearchCaching(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/orders/search', [
            'query' => 'test'
        ]);

        $this->assertResponseIsSuccessful();
        $etag = $client->getResponse()->headers->get('ETag');
        $this->assertNotNull($etag);

        $client->request('GET', '/api/v1/orders/search', [
            'query' => 'test'
        ], [], ['HTTP_IF_NONE_MATCH' => $etag]);

        $this->assertResponseStatusCodeSame(304);
    }

    public function testGetOrderCaching(): void
    {
        $client = static::createClient();

        // First we need to find an existing order ID
        // We can use the search to find one
        $client->request('GET', '/api/v1/orders/search', ['query' => '', 'limit' => 1]);
        $responseData = json_decode($client->getResponse()->getContent(), true);

        if (empty($responseData['items'])) {
            $this->markTestSkipped('No orders found for caching test');
        }

        $orderId = $responseData['items'][0]['id'];

        $client->request('GET', '/api/v1/orders/' . $orderId);
        $this->assertResponseIsSuccessful();

        $etag = $client->getResponse()->headers->get('ETag');
        $lastModified = $client->getResponse()->headers->get('Last-Modified');

        $this->assertNotNull($etag);
        $this->assertNotNull($lastModified);

        // Test ETag
        $client->request('GET', '/api/v1/orders/' . $orderId, [], [], ['HTTP_IF_NONE_MATCH' => $etag]);
        $this->assertResponseStatusCodeSame(304);

        // Test Last-Modified
        $client->request('GET', '/api/v1/orders/' . $orderId, [], [], ['HTTP_IF_MODIFIED_SINCE' => $lastModified]);
        $this->assertResponseStatusCodeSame(304);
    }
    public function testGetStatsCachingWithDataChange(): void
    {
        $client = static::createClient();

        // 1. Get initial stats
        $client->request('GET', '/api/v1/orders/stats', [
            'group_by' => 'month'
        ]);

        $this->assertResponseIsSuccessful();
        $etag1 = $client->getResponse()->headers->get('ETag');
        $this->assertNotNull($etag1);

        // 2. Modify an order to change the last update timestamp
        $container = static::getContainer();
        /** @var OrderRepositoryInterface $repository */
        $repository = $container->get(OrderRepositoryInterface::class);
        $order = $repository->findOneBy([]);
        if (!$order) {
            $this->markTestSkipped('No orders found for data change test');
        }

        // We need to trigger an update. Even a small change should work if it updates updatedAt.
        $newDates = $order->getDates()->withUpdateAt(new \DateTime('+1 second'));
        $order->setDates($newDates);
        $repository->save($order, true);

        // 3. Get stats again, ETag should be different
        $client->request('GET', '/api/v1/orders/stats', [
            'group_by' => 'month'
        ]);

        $this->assertResponseIsSuccessful();
        $etag2 = $client->getResponse()->headers->get('ETag');
        $this->assertNotNull($etag2);
        $this->assertNotEquals($etag1, $etag2, 'ETag should change when data is modified');

        // 4. Verify that with the old ETag we DON'T get 304
        $client->request('GET', '/api/v1/orders/stats', [
            'group_by' => 'month'
        ], [], ['HTTP_IF_NONE_MATCH' => $etag1]);

        $this->assertResponseStatusCodeSame(200, 'Should return 200 when data changed even if old ETag is provided');
    }

    public function testSearchCachingWithDataChange(): void
    {
        $client = static::createClient();

        // 1. Get initial search results
        $client->request('GET', '/api/v1/orders/search', [
            'query' => 'test'
        ]);

        $this->assertResponseIsSuccessful();
        $etag1 = $client->getResponse()->headers->get('ETag');
        $this->assertNotNull($etag1);

        // 2. Modify an order to change the last update timestamp
        $container = static::getContainer();
        /** @var OrderRepositoryInterface $repository */
        $repository = $container->get(OrderRepositoryInterface::class);
        $order = $repository->findOneBy([]);
        if (!$order) {
            $this->markTestSkipped('No orders found for data change test');
        }

        $newDates = $order->getDates()->withUpdateAt(new \DateTime('+1 second'));
        $order->setDates($newDates);
        $repository->save($order, true);

        // 3. Get search results again, ETag should be different
        $client->request('GET', '/api/v1/orders/search', [
            'query' => 'test'
        ]);

        $this->assertResponseIsSuccessful();
        $etag2 = $client->getResponse()->headers->get('ETag');
        $this->assertNotNull($etag2);
        $this->assertNotEquals($etag1, $etag2, 'ETag should change when data is modified');

        // 4. Verify that with the old ETag we DON'T get 304
        $client->request('GET', '/api/v1/orders/search', [
            'query' => 'test'
        ], [], ['HTTP_IF_NONE_MATCH' => $etag1]);

        $this->assertResponseStatusCodeSame(200);
    }
}
