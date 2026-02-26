<?php

namespace App\Controller\Api\v1;

use App\Application\UseCase\GetPriceUseCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

class PriceController extends AbstractController
{
    #[Route('/api/v1/price', name: 'api_v1_price', methods: ['GET'])]
    public function getPrice(Request $request, GetPriceUseCase $useCase): JsonResponse
    {
        $factory = $request->query->get('factory');
        $collection = $request->query->get('collection');
        $article = $request->query->get('article');

        if (!$factory || !$collection || !$article) {
            return new JsonResponse([
                'error' => 'Missing required parameters: factory, collection, article'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $priceDto = $useCase->execute($factory, $collection, $article);

            return new JsonResponse([
                'price' => $priceDto->price,
                'factory' => $priceDto->factory,
                'collection' => $priceDto->collection,
                'article' => $priceDto->article,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
                'status' => 'error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
