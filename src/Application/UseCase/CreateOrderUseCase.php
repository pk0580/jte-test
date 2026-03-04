<?php

namespace App\Application\UseCase;

use App\Domain\Repository\ArticleRepositoryInterface;
use App\Domain\Repository\PayTypeRepositoryInterface;
use App\Application\Dto\Soap\CreateOrderSoapRequestDto;
use App\Application\Dto\Soap\SoapOrderResponseDto;
use App\Domain\Factory\OrderFactory;
use App\Domain\Repository\OrderRepositoryInterface;

readonly class CreateOrderUseCase
{
    public function __construct(
        private OrderRepositoryInterface   $orderRepository,
        private PayTypeRepositoryInterface $payTypeRepository,
        private ArticleRepositoryInterface $articleRepository,
        private OrderFactory               $orderFactory
    ) {}

    public function execute(CreateOrderSoapRequestDto $request): SoapOrderResponseDto
    {
        try {
            $payType = $this->payTypeRepository->findById($request->payType);
            if (!$payType) {
                throw new \Exception('Payment type not found');
            }

            $articles = [];
            foreach ($request->articles as $articleDto) {
                $article = $this->articleRepository->findById($articleDto->articleId);
                if (!$article) {
                    throw new \Exception(sprintf('Article with ID %d not found', $articleDto->articleId));
                }
                $articles[] = [
                    'entity' => $article,
                    'dto' => $articleDto
                ];
            }

            $order = $this->orderFactory->createFromSoapRequest($request, $payType, $articles);

            $this->orderRepository->save($order);

            return new SoapOrderResponseDto(true, $order->getId());
        } catch (\Exception $e) {
            return new SoapOrderResponseDto(false, null, $e->getMessage());
        }
    }
}
