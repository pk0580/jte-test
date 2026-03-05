<?php

namespace App\Controller;

use App\Domain\Repository\OrderSearchInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class HealthCheckController extends AbstractController
{
    #[Route('/health', name: 'health_check', methods: ['GET'])]
    public function index(
        EntityManagerInterface $entityManager,
        OrderSearchInterface $searchProvider
    ): JsonResponse {
        $status = [
            'status' => 'ok',
            'timestamp' => date('c'),
            'services' => [
                'database' => 'unknown',
                'manticore' => 'unknown',
            ],
        ];

        $healthy = true;

        // Check Database
        try {
            $connection = $entityManager->getConnection();
            if ($connection->getNativeConnection()) {
                $status['services']['database'] = 'ok';
            } else {
                $status['services']['database'] = 'fail';
                $healthy = false;
            }
        } catch (\Throwable $e) {
            $status['services']['database'] = 'fail: ' . $e->getMessage();
            $healthy = false;
        }

        // Check Manticore
        try {
            if ($searchProvider->ping()) {
                $status['services']['manticore'] = 'ok';
            } else {
                $status['services']['manticore'] = 'fail';
                $healthy = false;
            }
        } catch (\Throwable $e) {
            $status['services']['manticore'] = 'fail: ' . $e->getMessage();
            $healthy = false;
        }

        if (!$healthy) {
            $status['status'] = 'error';
            return new JsonResponse($status, 503);
        }

        return new JsonResponse($status);
    }
}
