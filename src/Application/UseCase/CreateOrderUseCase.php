<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Common\TransactionManagerInterface;
use App\Application\Dto\Soap\CreateOrderSoapRequestDto;
use App\Application\Dto\Soap\SoapOrderArticleDto;
use App\Application\Dto\Soap\SoapOrderResponseDto;
use App\Domain\Dto\CreateOrderDto;
use App\Domain\Dto\OrderArticleDto;
use App\Domain\Exception\ArticleNotFoundException;
use App\Domain\Factory\OrderFactory;
use App\Domain\Repository\OrderRepositoryInterface;

readonly class CreateOrderUseCase
{
    public function __construct(
        private OrderRepositoryInterface   $orderRepository,
        private OrderFactory               $orderFactory,
        private TransactionManagerInterface $transactionManager
    ) {
    }

    public function execute(CreateOrderSoapRequestDto $request): SoapOrderResponseDto
    {
        $articles = array_map(
            /** @param SoapOrderArticleDto|array<string, mixed> $a */
            function ($a) {
                // Если пришел массив (из-за особенностей денормализации SOAP вложенных объектов), приведем к DTO
                if (is_array($a)) {
                    $a = new SoapOrderArticleDto(
                        id: (int)($a['articleId'] ?? 0),
                        amount: (string)($a['amount'] ?? '0'),
                        price: (string)($a['price'] ?? '0'),
                        weight: (string)($a['weight'] ?? '0')
                    );
                }

                /** @var SoapOrderArticleDto $a */
                return new OrderArticleDto($a->id, (float)$a->amount, (float)$a->price, (float)$a->weight);
            },
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
            } catch (ArticleNotFoundException $e) {
                return new SoapOrderResponseDto(false, null, $e->getMessage());
            } catch (\Exception $e) {
                return new SoapOrderResponseDto(false, null, 'An unexpected error occurred during order creation');
            }
        });
    }
}
