<?php

namespace App\Application\UseCase;

use App\Application\Dto\OrderArticleResponseDto;
use App\Application\Dto\OrderResponseDto;
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
                $article->getId(),
                $article->getArticleId(),
                $article->getAmount(),
                $article->getPrice(),
                $article->getWeight()
            );
        }

        return new OrderResponseDto(
            $order->getId(),
            $order->getClientName(),
            $order->getClientSurname(),
            $order->getEmail(),
            $order->getPayType(),
            $order->getCreateDate()->format('Y-m-d H:i:s'),
            $articles
        );
    }
}
