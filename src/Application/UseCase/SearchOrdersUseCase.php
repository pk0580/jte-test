<?php

namespace App\Application\UseCase;

use App\Application\Dto\OrderArticleResponseDto;
use App\Application\Dto\OrderResponseDto;
use App\Domain\Repository\OrderSearchInterface;

readonly class SearchOrdersUseCase
{
    public function __construct(
        private OrderSearchInterface $orderSearch
    ) {}

    /**
     * @param string $query
     * @param int $page
     * @param int $limit
     * @return OrderResponseDto[]
     */
    public function execute(string $query, int $page = 1, int $limit = 10): array
    {
        $orders = $this->orderSearch->search($query, $page, $limit);

        return array_map(function ($order) {
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
                $order->getClientName() ?? '',
                $order->getClientSurname() ?? '',
                $order->getEmail() ?? '',
                $order->getPayType(),
                $order->getCreateDate()->format('Y-m-d H:i:s'),
                $articles
            );
        }, $orders);
    }
}
