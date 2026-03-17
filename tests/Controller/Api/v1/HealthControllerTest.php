<?php

namespace App\Tests\Controller\Api\v1;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

class HealthControllerTest extends WebTestCase
{
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
    }
}
