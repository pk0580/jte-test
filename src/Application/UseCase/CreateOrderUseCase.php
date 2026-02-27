<?php

namespace App\Application\UseCase;

use App\Application\Dto\Soap\CreateOrderSoapRequestDto;
use App\Application\Dto\Soap\SoapOrderResponseDto;
use App\Domain\Entity\Order;
use App\Domain\Entity\OrderArticle;
use App\Domain\Repository\OrderRepositoryInterface;

readonly class CreateOrderUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository
    ) {}

    public function execute(CreateOrderSoapRequestDto $request): SoapOrderResponseDto
    {
        try {
            $order = new Order();
            $order->setHash(bin2hex(random_bytes(16)));
            $order->setToken(bin2hex(random_bytes(32)));
            $order->setClientName($request->client_name);
            $order->setClientSurname($request->client_surname);
            $order->setEmail($request->email);
            $order->setPayType($request->pay_type);
            $order->setLocale('ru');
            $order->setCurrency('EUR');
            $order->setMeasure('m');
            $order->setName('Order from SOAP');
            $order->setCreateDate(new \DateTime());
            $order->setStatus(1);

            foreach ($request->articles as $articleDto) {
                $article = new OrderArticle();
                $article->setOrder($order);
                $article->setArticleId($articleDto->article_id);
                $article->setAmount($articleDto->amount);
                $article->setPrice($articleDto->price);
                $article->setWeight($articleDto->weight);
                $article->setPackagingCount('0');
                $article->setPallet('0');
                $article->setPackaging('0');

                $order->addArticle($article);
            }

            $this->orderRepository->save($order);

            return new SoapOrderResponseDto(true, $order->getId());
        } catch (\Exception $e) {
            return new SoapOrderResponseDto(false, null, $e->getMessage());
        }
    }
}
