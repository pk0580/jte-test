<?php

namespace App\Application\Service;

use App\Application\Dto\Soap\CreateOrderSoapRequestDto;
use App\Application\UseCase\CreateOrderUseCase;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class SoapOrderService
{
    public function __construct(
        private CreateOrderUseCase    $useCase,
        private ValidatorInterface    $validator,
        private NormalizerInterface   $normalizer,
        private DenormalizerInterface $denormalizer
    ) {}

    /**
     * @param mixed $parameters
     * @return array<string, mixed>
     * @throws \SoapFault
     */
    public function createOrder(mixed $parameters): array
    {
        // Преобразуем SoapObject в массив
        $parametersArray = (array) $parameters;

        // Если передан массив объектов, нормализуем articles.item
        if (isset($parametersArray['articles']) && is_object($parametersArray['articles'])) {
            $articlesObj = (array)$parametersArray['articles'];
            if (isset($articlesObj['item'])) {
                $parametersArray['articles'] = is_array($articlesObj['item']) ? $articlesObj['item'] : [$articlesObj['item']];
            }
        }

        /** @var CreateOrderSoapRequestDto $dto */
        $dto = $this->denormalizer->denormalize($parametersArray, CreateOrderSoapRequestDto::class);

        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()][] = $violation->getMessage();
            }

            // Формируем детальное сообщение об ошибке для SoapFault
            $faultString = 'Validation failed';
            $detail = ['errors' => []];
            foreach ($errors as $path => $messages) {
                foreach ($messages as $message) {
                    $detail['errors'][] = ['field' => $path, 'message' => $message];
                }
            }

            throw new \SoapFault('Client', $faultString, null, $detail);
        }

        $responseDto = $this->useCase->execute($dto);

        // Используем Normalizer для нормализации ответа
        return (array)$this->normalizer->normalize($responseDto, null, [
            AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
        ]);
    }
}
