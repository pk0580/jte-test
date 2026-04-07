<?php

namespace App\Controller\Api\v1;

use App\Domain\Repository\OrderSearchInterface;
use Doctrine\DBAL\Connection;
use Predis\ClientInterface;
use Doctrine\Migrations\DependencyInjection\DependencyInjectionAdapter;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\MigrationsRepository;
use Doctrine\Migrations\Plan\MigrationPlanCalculator;
use Doctrine\Migrations\Version\MigrationStatusCalculator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Routing\Attribute\Route;

class HealthController extends AbstractController
{
    #[Route('/api/v1/health/live', name: 'api_v1_health_live', methods: ['GET'])]
    public function live(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok',
            'timestamp' => time(),
        ]);
    }

    #[Route('/api/v1/health/ready', name: 'api_v1_health_ready', methods: ['GET'])]
    public function ready(
        OrderSearchInterface $orderSearch,
        Connection $connection,
        ClientInterface $redis,
        TransportInterface $messenger_transport_async,
        MigrationStatusCalculator $statusCalculator,
        MigrationsRepository $migrationsRepository
    ): JsonResponse {
        $isManticoreHealthy = $orderSearch->ping();

        $isDbHealthy = true;
        try {
            $connection->executeQuery('SELECT 1');
        } catch (\Exception) {
            $isDbHealthy = false;
        }

        $isRedisHealthy = true;
        try {
            $redis->ping();
        } catch (\Exception) {
            $isRedisHealthy = false;
        }

        $isMigrationsUpToDate = true;
        try {
            $newMigrations = $statusCalculator->getNewMigrations();
            $isMigrationsUpToDate = count($newMigrations) === 0;
        } catch (\Exception) {
            $isMigrationsUpToDate = false;
        }

        $queueLag = 0;
        if ($messenger_transport_async instanceof MessageCountAwareInterface) {
            $queueLag = $messenger_transport_async->getMessageCount();
        }

        $isHealthy = $isManticoreHealthy && $isDbHealthy && $isRedisHealthy && $isMigrationsUpToDate;
        $status = $isHealthy ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE;

        return new JsonResponse([
            'status' => $isHealthy ? 'ok' : 'error',
            'services' => [
                'manticore' => $isManticoreHealthy ? 'healthy' : 'unhealthy',
                'database' => $isDbHealthy ? 'healthy' : 'unhealthy',
                'redis' => $isRedisHealthy ? 'healthy' : 'unhealthy',
                'migrations' => $isMigrationsUpToDate ? 'up_to_date' : 'pending',
            ],
            'metrics' => [
                'queue_lag' => $queueLag,
            ]
        ], $status);
    }

    #[Route('/api/v1/health', name: 'api_v1_health', methods: ['GET'])]
    public function check(
        OrderSearchInterface $orderSearch,
        Connection $connection,
        ClientInterface $redis,
        TransportInterface $messenger_transport_async
    ): JsonResponse {
        $isManticoreHealthy = $orderSearch->ping();

        $isDbHealthy = true;
        try {
            $connection->executeQuery('SELECT 1');
        } catch (\Exception) {
            $isDbHealthy = false;
        }

        $isRedisHealthy = true;
        try {
            $redis->ping();
        } catch (\Exception) {
            $isRedisHealthy = false;
        }

        $queueLag = 0;
        if ($messenger_transport_async instanceof MessageCountAwareInterface) {
            $queueLag = $messenger_transport_async->getMessageCount();
        }

        $isHealthy = $isManticoreHealthy && $isDbHealthy && $isRedisHealthy;
        $status = $isHealthy ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE;

        return new JsonResponse([
            'status' => $isHealthy ? 'ok' : 'error',
            'services' => [
                'manticore' => $isManticoreHealthy ? 'healthy' : 'unhealthy',
                'database' => $isDbHealthy ? 'healthy' : 'unhealthy',
                'redis' => $isRedisHealthy ? 'healthy' : 'unhealthy',
            ],
            'metrics' => [
                'queue_lag' => $queueLag,
            ]
        ], $status);
    }
}
