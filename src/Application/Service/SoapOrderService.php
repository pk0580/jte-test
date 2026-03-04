<?php

namespace App\Application\Service;

use App\Application\Dto\Soap\CreateOrderSoapRequestDto;
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

    public function createOrder($parameters): array
    {
        $parametersArray = json_decode(json_encode($parameters), true);

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

        /** @var CreateOrderSoapRequestDto $dto */
        $dto = $this->serializer->denormalize($parametersArray, CreateOrderSoapRequestDto::class);

        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage());
            }
            throw new \SoapFault('Client', 'Validation failed: ' . implode('; ', $errors));
        }

        $responseDto = $this->useCase->execute($dto);

        return $this->serializer->normalize($responseDto);
    }
}
