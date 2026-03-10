<?php

namespace App\Application\Soap;

use App\Application\Dto\Soap\CreateOrderSoapRequestDto;
use App\Application\Dto\Soap\SoapOrderResponseDto;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
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
     * @throws ExceptionInterface
     */
    public function denormalizeRequest(mixed $parameters): CreateOrderSoapRequestDto
    {
        // Преобразуем SoapObject в массив
        $parametersArray = (array) $parameters;

        // Если пришло из SoapServer в режиме document/literal, параметры могут быть обернуты
        if (isset($parametersArray['clientName']) === false && count($parametersArray) === 1) {
             $firstKey = array_key_first($parametersArray);
             if (is_object($parametersArray[$firstKey]) || is_array($parametersArray[$firstKey])) {
                 $parametersArray = (array)$parametersArray[$firstKey];
             }
        }

        // Если передан массив объектов, нормализуем articles.item
        if (isset($parametersArray['articles']) && (is_object($parametersArray['articles']) || is_array($parametersArray['articles']))) {
            $articlesObj = (array)$parametersArray['articles'];
            if (isset($articlesObj['item'])) {
                $items = $articlesObj['item'];
                if (is_object($items)) {
                    $parametersArray['articles'] = [(array)$items];
                } elseif (is_array($items)) {
                    $parametersArray['articles'] = array_map(fn($item) => is_object($item) ? (array)$item : $item, $items);
                }
            }
        }

        /** @var CreateOrderSoapRequestDto $dto */
        $dto = $this->denormalizer->denormalize($parametersArray, CreateOrderSoapRequestDto::class);

        return $dto;
    }

    /**
     * @param SoapOrderResponseDto $responseDto
     * @return array<string, mixed>
     * @throws ExceptionInterface
     */
    public function normalizeResponse(SoapOrderResponseDto $responseDto): array
    {
        $data = (array)$this->normalizer->normalize($responseDto, null, [
            AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
        ]);

        // SoapServer при использовании associative array игнорирует null, что убирает теги из XML.
        // Чтобы теги <orderId> и <message> ПРИСУТСТВОВАЛИ (как того ожидает тест), заменим null на дефолты.
        if (!isset($data['orderId']) || $data['orderId'] === null) {
             $data['orderId'] = 0;
        }
        if (!isset($data['message']) || $data['message'] === null) {
             $data['message'] = '';
        }

        return $data;
    }
}
