<?php

namespace App\Domain\Factory;

use App\Domain\Entity\PayType;
use App\Domain\ValueObject\CustomerInfo;
use App\Domain\ValueObject\DeliveryAddress;
use App\Domain\ValueObject\DeliveryConfig;
use App\Domain\ValueObject\DeliveryTerms;
use App\Domain\ValueObject\FinancialTerms;
use App\Domain\ValueObject\ManagerInfo;
use App\Application\Dto\Soap\CreateOrderSoapRequestDto;
use App\Domain\Entity\Order;
use App\Domain\Entity\OrderArticle;

use App\Domain\Service\OrderPriceCalculator;

class OrderFactory
{
    public function __construct(
        private readonly OrderPriceCalculator $priceCalculator
    ) {}

    public function createFromSoapRequest(CreateOrderSoapRequestDto $request, PayType $payType, array $articlesData): Order
    {
        $customerInfo = new CustomerInfo(
            name: $request->clientName,
            surname: $request->clientSurname,
            email: $request->email
        );

        $order = new Order(
            payType: $payType,
            name: 'Order from SOAP',
            customerInfo: $customerInfo,
            deliveryAddress: new DeliveryAddress(),
            deliveryTerms: new DeliveryTerms(),
            managerInfo: new ManagerInfo(),
            financialTerms: new FinancialTerms(),
            deliveryConfig: new DeliveryConfig(),
            locale: 'en',
            currency: 'EUR',
            measure: 'unit'
        );

        foreach ($articlesData as $data) {
            $articleEntity = $data['entity'];
            $articleDto = $data['dto'];

            $orderArticle = new OrderArticle(
                order: $order,
                article: $articleEntity,
                amount: (string)$articleDto->amount,
                price: (string)$articleDto->price,
                weight: (string)$articleDto->weight,
                packagingCount: '1',
                pallet: '0',
                packaging: 'unit'
            );

            $order->addArticle($orderArticle);
        }

        $this->priceCalculator->recalculate($order);

        return $order;
    }
}
