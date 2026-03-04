<?php

namespace App\Application\UseCase;

use App\Application\Dto\Soap\CreateOrderSoapRequestDto;
use App\Application\Dto\Soap\SoapOrderResponseDto;
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
        return $this->transactionManager->wrapInTransaction(function () use ($request) {
            try {
                $order = $this->orderFactory->createFromSoapRequest($request);

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
