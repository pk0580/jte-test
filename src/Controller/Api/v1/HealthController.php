<?php

namespace App\Controller\Api\v1;

use App\Domain\Repository\OrderSearchInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HealthController extends AbstractController
{
    #[Route('/api/v1/health', name: 'api_v1_health', methods: ['GET'])]
    public function check(OrderSearchInterface $orderSearch): JsonResponse
    {
        $isManticoreHealthy = $orderSearch->ping();

        $status = $isManticoreHealthy ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE;

        return new JsonResponse([
            'status' => $isManticoreHealthy ? 'ok' : 'error',
            'services' => [
                'manticore' => $isManticoreHealthy ? 'healthy' : 'unhealthy'
            ]
        ], $status);
    }
}
