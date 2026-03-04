<?php

namespace App\Application\UseCase;

use App\Application\Dto\Order\OrderArticleResponseDto;
use App\Application\Dto\Order\OrderResponseDto;
use App\Application\Dto\Search\SearchResultDto;
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
     * @param int|null $lastId
     * @param int|null $status
     * @return SearchResultDto
     */
    public function execute(string $query, int $page = 1, int $limit = 10, ?int $lastId = null, ?int $status = null): SearchResultDto
    {
        $searchResult = $this->orderSearch->search($query, $page, $limit, $lastId, $status);

        $items = array_map(function ($order) {
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
                $order->getClientName() ?? '',
                $order->getClientSurname() ?? '',
                $order->getEmail() ?? '',
                (int)$order->getPayType()->getId(),
                $order->getCreateDate()->format('Y-m-d H:i:s'),
                $articles
            );
        }, $searchResult->items);

        return new SearchResultDto($items, $searchResult->total);
    }
}
