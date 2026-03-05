<?php

namespace App\Application\UseCase;

use App\Application\Dto\Order\OrderArticleResponseDto;
use App\Application\Dto\Order\OrderResponseDto;
use App\Domain\Repository\OrderRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

readonly class GetOrderUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository
    ) {}

    public function execute(int $id): OrderResponseDto
    {
        $order = $this->orderRepository->findById($id);

        if (!$order) {
            throw new NotFoundHttpException(sprintf('Order with ID %d not found', $id));
        }

        $articles = [];
        foreach ($order->getArticles() as $article) {
            $articles[] = new OrderArticleResponseDto(
                (int)$article->getId(),
                (int)$article->getArticle()->getId(),
                $article->getAmount(),
                $article->getPrice(),
                $article->getWeight()
            );
        }

        return new OrderResponseDto(
            (int)$order->getId(),
            $order->getCustomerInfo()->name ?? '',
            $order->getCustomerInfo()->surname ?? '',
            $order->getCustomerInfo()->email ?? '',
            (int)$order->getPayType()->getId(),
            $order->getDates()->createAt->format('Y-m-d H:i:s'),
            $articles
        );
    }
}
