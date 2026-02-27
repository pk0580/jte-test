<?php

namespace App\Application\Service;

use App\Application\Dto\Soap\CreateOrderSoapRequestDto;
use App\Application\Dto\Soap\SoapOrderArticleDto;
use App\Application\UseCase\CreateOrderUseCase;

readonly class SoapOrderService
{
    public function __construct(
        private CreateOrderUseCase $useCase
    ) {}

    public function createOrder($parameters): array
    {
        $articlesData = $parameters->articles->item;
        if (!is_array($articlesData)) {
            $articlesData = [$articlesData];
        }

        $articles = [];
        foreach ($articlesData as $item) {
            $articles[] = new SoapOrderArticleDto(
                (int) $item->article_id,
                (string) $item->amount,
                (string) $item->price,
                (string) $item->weight
            );
        }

        $dto = new CreateOrderSoapRequestDto(
            (string) $parameters->client_name,
            (string) $parameters->client_surname,
            (string) $parameters->email,
            (int) $parameters->pay_type,
            $articles
        );

        $responseDto = $this->useCase->execute($dto);

        return [
            'success' => $responseDto->success,
            'order_id' => $responseDto->order_id,
            'message' => $responseDto->message,
        ];
    }
}
