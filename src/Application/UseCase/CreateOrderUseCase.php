<?php

namespace App\Application\UseCase;

use App\Domain\Repository\ArticleRepositoryInterface;
use App\Domain\Repository\PayTypeRepositoryInterface;
use App\Application\Dto\Soap\CreateOrderSoapRequestDto;
use App\Application\Dto\Soap\SoapOrderResponseDto;
use App\Domain\Factory\OrderFactory;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Application\Common\TransactionManagerInterface;

readonly class CreateOrderUseCase
{
    public function __construct(
        private OrderRepositoryInterface   $orderRepository,
        private PayTypeRepositoryInterface $payTypeRepository,
        private ArticleRepositoryInterface $articleRepository,
        private OrderFactory               $orderFactory,
        private TransactionManagerInterface $transactionManager
    ) {}

    public function execute(CreateOrderSoapRequestDto $request): SoapOrderResponseDto
    {
        return $this->transactionManager->wrapInTransaction(function () use ($request) {
            try {
                $payType = $this->payTypeRepository->findById($request->payType);
                if (!$payType) {
                    throw new \App\Domain\Exception\ArticleNotFoundException(sprintf('Payment type with ID %d not found', $request->payType));
                }

                $articles = [];
                foreach ($request->articles as $articleDto) {
                    $article = $this->articleRepository->findById($articleDto->articleId);
                    if (!$article) {
                        throw new \App\Domain\Exception\ArticleNotFoundException(sprintf('Article with ID %d not found', $articleDto->articleId));
                    }
                    $articles[] = [
                        'entity' => $article,
                        'dto' => $articleDto
                    ];
                }

                $order = $this->orderFactory->createFromSoapRequest($request, $payType, $articles);

                $this->orderRepository->save($order);

                return new SoapOrderResponseDto(true, $order->getId());
            } catch (\App\Domain\Exception\ArticleNotFoundException $e) {
                return new SoapOrderResponseDto(false, null, $e->getMessage());
            } catch (\Exception $e) {
                return new SoapOrderResponseDto(false, null, 'An unexpected error occurred during order creation');
            }
        });
    }
}
