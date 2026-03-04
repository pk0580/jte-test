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

use App\Domain\Repository\ArticleRepositoryInterface;
use App\Domain\Repository\PayTypeRepositoryInterface;
use App\Domain\Service\OrderPriceCalculator;

class OrderFactory
{
    public function __construct(
        private readonly OrderPriceCalculator $priceCalculator,
        private readonly ArticleRepositoryInterface $articleRepository,
        private readonly PayTypeRepositoryInterface $payTypeRepository
    ) {}

    public function createFromSoapRequest(CreateOrderSoapRequestDto $request): Order
    {
        $payType = $this->payTypeRepository->findById($request->payType);
        if (!$payType) {
            throw new \App\Domain\Exception\ArticleNotFoundException(sprintf('Payment type with ID %d not found', $request->payType));
        }

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

        foreach ($request->articles as $articleDto) {
            $article = $this->articleRepository->findById($articleDto->articleId);
            if (!$article) {
                throw new \App\Domain\Exception\ArticleNotFoundException(sprintf('Article with ID %d not found', $articleDto->articleId));
            }

            $orderArticle = new OrderArticle(
                order: $order,
                article: $article,
                amount: (string)$articleDto->amount,
                price: (string)$articleDto->price,
                weight: (string)$articleDto->weight,
                packagingCount: '1',
                pallet: '0',
                packaging: '1',
                measure: 'unit'
            );

            $order->addArticle($orderArticle);
        }

        $this->priceCalculator->recalculate($order);

        return $order;
    }
}
