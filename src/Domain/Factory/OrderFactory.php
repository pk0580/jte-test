<?php

namespace App\Domain\Factory;

use App\Domain\Entity\PayType;
use App\Domain\ValueObject\DeliveryAddress;
use App\Domain\ValueObject\DeliveryConfig;
use App\Domain\ValueObject\DeliveryTerms;
use App\Domain\ValueObject\FinancialTerms;
use App\Domain\ValueObject\ManagerInfo;
use App\Application\Dto\Soap\CreateOrderSoapRequestDto;
use App\Domain\Entity\Order;
use App\Domain\Entity\OrderArticle;

class OrderFactory
{
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
            locale: 'en',
            currency: 'EUR',
            measure: 'unit',
            customerInfo: $customerInfo,
            deliveryAddress: new DeliveryAddress(),
            deliveryTerms: new DeliveryTerms(),
            managerInfo: new ManagerInfo(),
            financialTerms: new FinancialTerms(),
            deliveryConfig: new DeliveryConfig()
        );

        foreach ($articlesData as $data) {
            $articleEntity = $data['entity'];
            $articleDto = $data['dto'];

            $orderArticle = new OrderArticle();
            $orderArticle->setOrder($order);
            $orderArticle->setArticle($articleEntity);
            $orderArticle->setPrice($articleDto->price);
            $orderArticle->setAmount($articleDto->amount);
            $orderArticle->setWeight($articleDto->weight);
            $orderArticle->setPackagingCount('0');
            $orderArticle->setPallet('0');
            $orderArticle->setPackaging('0');

            $order->addArticle($orderArticle);
        }


        return $order;
    }
}
