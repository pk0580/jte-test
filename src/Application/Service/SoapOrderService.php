<?php

namespace App\Application\Service;

use App\Application\Dto\Soap\CreateOrderSoapRequestDto;
use App\Application\UseCase\CreateOrderUseCase;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
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
        // Преобразуем параметры в массив для удобства обработки структур SOAP (item в массивах)
        $parametersArray = json_decode((string)json_encode($parameters), true);
        if (!is_array($parametersArray)) {
            $parametersArray = [];
        }

        // Унификация структуры для массивов (articles.item -> articles)
        if (isset($parametersArray['articles']['item'])) {
            $items = $parametersArray['articles']['item'];
            $parametersArray['articles'] = isset($items[0]) ? $items : [$items];
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

        // Используем Serializer для нормализации ответа
        return (array)$this->serializer->normalize($responseDto, null, [
            AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
        ]);
    }
}
