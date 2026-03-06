<?php

namespace App\Application\Service;

use App\Application\UseCase\CreateOrderUseCase;
use App\Application\Soap\SoapConverter;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class SoapOrderService
{
    public function __construct(
        private CreateOrderUseCase    $useCase,
        private ValidatorInterface    $validator,
        private SoapConverter         $soapConverter
    ) {}

    /**
     * @param mixed $parameters
     * @return array<string, mixed>
     * @throws \SoapFault
     */
    public function createOrder(mixed $parameters): array
    {
        $dto = $this->soapConverter->denormalizeRequest($parameters);

        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $faultString = 'Validation failed';
            $detail = ['errors' => []];
            foreach ($violations as $violation) {
                $detail['errors'][] = [
                    'field' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage()
                ];
            }

            throw new \SoapFault('Client', $faultString, null, $detail);
        }

        $responseDto = $this->useCase->execute($dto);

        return $this->soapConverter->normalizeResponse($responseDto);
    }
}
