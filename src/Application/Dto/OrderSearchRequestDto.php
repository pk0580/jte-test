<?php

namespace App\Application\Dto;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

readonly class OrderSearchRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Query parameter is required')]
        #[Assert\Length(min: 3, minMessage: 'Query must be at least 3 characters long')]
        public string $query,

        #[Assert\GreaterThanOrEqual(value: 1, message: 'Page must be greater than or equal to 1')]
        public int    $page = 1,

        #[Assert\Range(min: 1, max: 100, notInRangeMessage: 'Limit must be between 1 and 100')]
        public int    $limit = 10,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            query: $request->query->get('query', ''),
            page: $request->query->getInt('page', 1),
            limit: $request->query->getInt('limit', 10),
        );
    }
}
