<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api\v1;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HealthControllerTest extends WebTestCase
{
    #[RunInSeparateProcess]
    public function testHealthLiveSuccess(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/health/live');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('status', $responseData);
        $this->assertEquals('ok', $responseData['status']);
    }

    #[RunInSeparateProcess]
    public function testHealthReadySuccess(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/health/ready');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('status', $responseData);
        $this->assertEquals('ok', $responseData['status']);
        $this->assertEquals('healthy', $responseData['services']['manticore']);
        $this->assertEquals('healthy', $responseData['services']['database']);
        $this->assertEquals('healthy', $responseData['services']['redis']);
        $this->assertEquals('up_to_date', $responseData['services']['migrations']);
        $this->assertArrayHasKey('queue_lag', $responseData['metrics']);
    }

    #[RunInSeparateProcess]
    public function testHealthCheckSuccess(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/health');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('status', $responseData);
        $this->assertEquals('ok', $responseData['status']);
        $this->assertEquals('healthy', $responseData['services']['manticore']);
        $this->assertEquals('healthy', $responseData['services']['database']);
        $this->assertEquals('healthy', $responseData['services']['redis']);
        $this->assertArrayHasKey('queue_lag', $responseData['metrics']);
    }
}
