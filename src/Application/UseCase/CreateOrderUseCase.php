<?php

namespace App\Application\UseCase;

use App\Application\Dto\Soap\CreateOrderSoapRequestDto;
use App\Application\Dto\Soap\SoapOrderResponseDto;
use App\Domain\Factory\OrderFactory;
use App\Domain\Repository\OrderRepositoryInterface;

readonly class CreateOrderUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private OrderFactory             $orderFactory
    ) {}

    public function execute(CreateOrderSoapRequestDto $request): SoapOrderResponseDto
    {
        try {
            $order = $this->orderFactory->createFromSoapRequest($request);

            $this->orderRepository->save($order);

            return new SoapOrderResponseDto(true, $order->getId());
        } catch (\Exception $e) {
            return new SoapOrderResponseDto(false, null, $e->getMessage());
        }
    }
}
