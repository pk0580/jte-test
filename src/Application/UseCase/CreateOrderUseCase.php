<?php

namespace App\Application\UseCase;

use App\Application\Dto\Soap\CreateOrderSoapRequestDto;
use App\Application\Dto\Soap\SoapOrderResponseDto;
use App\Domain\Dto\CreateOrderDto;
use App\Domain\Dto\OrderArticleDto;
use App\Domain\Factory\OrderFactory;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Application\Common\TransactionManagerInterface;

readonly class CreateOrderUseCase
{
    public function __construct(
        private OrderRepositoryInterface   $orderRepository,
        private OrderFactory               $orderFactory,
        private TransactionManagerInterface $transactionManager
    ) {}

    public function execute(CreateOrderSoapRequestDto $request): SoapOrderResponseDto
    {
        $articles = array_map(
            fn($a) => new OrderArticleDto($a->articleId, (float)$a->amount, (float)$a->price, (float)$a->weight),
            $request->articles
        );

        $dto = new CreateOrderDto(
            clientName: $request->clientName,
            clientSurname: $request->clientSurname,
            email: $request->email,
            payType: $request->payType,
            articles: $articles
        );

        return $this->transactionManager->wrapInTransaction(function () use ($dto) {
            try {
                $order = $this->orderFactory->create($dto);

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
