<?php

namespace App\Application\Service;

use App\Application\Dto\Soap\CreateOrderSoapRequestDto;
use App\Application\Dto\Soap\SoapOrderArticleDto;
use App\Application\UseCase\CreateOrderUseCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class SoapOrderService
{
    public function __construct(
        private CreateOrderUseCase $useCase,
        private ValidatorInterface $validator
    ) {}

    public function createOrder($parameters): array
    {
        $articlesData = $parameters->articles->item ?? [];
        if (!is_array($articlesData)) {
            $articlesData = [$articlesData];
        }

        $articles = [];
        foreach ($articlesData as $item) {
            $articles[] = new SoapOrderArticleDto(
                (int) ($item->article_id ?? 0),
                (string) ($item->amount ?? ''),
                (string) ($item->price ?? ''),
                (string) ($item->weight ?? '')
            );
        }

        $dto = new CreateOrderSoapRequestDto(
            (string) ($parameters->client_name ?? ''),
            (string) ($parameters->client_surname ?? ''),
            (string) ($parameters->email ?? ''),
            (int) ($parameters->pay_type ?? 0),
            $articles
        );

        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage());
            }
            throw new \SoapFault('Client', 'Validation failed: ' . implode('; ', $errors));
        }

        $responseDto = $this->useCase->execute($dto);

        return [
            'success' => $responseDto->success,
            'order_id' => $responseDto->order_id,
            'message' => $responseDto->message,
        ];
    }
}
