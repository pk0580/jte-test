<?php

namespace App\Application\Service;

use App\Application\Dto\Soap\CreateOrderSoapRequestDto;
use App\Application\Dto\Soap\SoapOrderArticleDto;
use App\Application\UseCase\CreateOrderUseCase;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class SoapOrderService
{
    public function __construct(
        private CreateOrderUseCase  $useCase,
        private ValidatorInterface  $validator,
        private SerializerInterface $serializer
    ) {}

    /**
     * @param mixed $parameters
     * @return array<string, mixed>
     * @throws \SoapFault
     */
    public function createOrder(mixed $parameters): array
    {
        $parametersArray = json_decode((string)json_encode($parameters), true);
        if (!is_array($parametersArray)) {
            $parametersArray = [];
        }

        // Обработка случая, когда articles.item может быть как массивом, так и одиночным объектом
        if (isset($parametersArray['articles']['item'])) {
            $items = $parametersArray['articles']['item'];
            if (!isset($items[0])) {
                $parametersArray['articles'] = [$items];
            } else {
                $parametersArray['articles'] = $items;
            }
        } else {
            $parametersArray['articles'] = [];
        }

        // Ручное создание DTO для обхода проблем с глобальным name_converter (camelCase -> snake_case)
        if (is_array($parametersArray['articles'])) {
            $articles = [];
            foreach ($parametersArray['articles'] as $articleData) {
                if (is_array($articleData)) {
                    $articles[] = new SoapOrderArticleDto(
                        (int)($articleData['articleId'] ?? 0),
                        (string)($articleData['amount'] ?? '0'),
                        (string)($articleData['price'] ?? '0'),
                        (string)($articleData['weight'] ?? '0')
                    );
                }
            }
            $parametersArray['articles'] = $articles;
        }

        $dto = new CreateOrderSoapRequestDto(
            (string)($parametersArray['clientName'] ?? ''),
            (string)($parametersArray['clientSurname'] ?? ''),
            (string)($parametersArray['email'] ?? ''),
            (int)($parametersArray['payType'] ?? 0),
            $parametersArray['articles']
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

        // Ручная нормализация ответа для SOAP, чтобы гарантировать camelCase и наличие всех полей
        return [
            'success' => $responseDto->success,
            'orderId' => $responseDto->orderId,
            'message' => $responseDto->message,
        ];
    }
}
