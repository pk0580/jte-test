<?php

namespace App\Application\Soap;

use App\Application\Dto\Soap\CreateOrderSoapRequestDto;
use App\Application\Dto\Soap\SoapOrderResponseDto;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

readonly class SoapConverter
{
    public function __construct(
        private NormalizerInterface   $normalizer,
        private DenormalizerInterface $denormalizer
    ) {}

    /**
     * @param mixed $parameters
     * @return CreateOrderSoapRequestDto
     */
    public function denormalizeRequest(mixed $parameters): CreateOrderSoapRequestDto
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

        return $dto;
    }

    /**
     * @param SoapOrderResponseDto $responseDto
     * @return array<string, mixed>
     */
    public function normalizeResponse(SoapOrderResponseDto $responseDto): array
    {
        return (array)$this->normalizer->normalize($responseDto, null, [
            AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
        ]);
    }
}
