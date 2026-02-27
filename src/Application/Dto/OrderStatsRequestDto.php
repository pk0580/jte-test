<?php

namespace App\Application\Dto;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

readonly class OrderStatsRequestDto
{
    public function __construct(
        #[Assert\Choice(choices: ['day', 'month', 'year'], message: 'Invalid group_by parameter. Allowed: day, month, year')]
        public string $groupBy = 'day',

        #[Assert\GreaterThanOrEqual(value: 1, message: 'Page must be greater than or equal to 1')]
        public int    $page = 1,

        #[Assert\Range(min: 1, max: 100, notInRangeMessage: 'Limit must be between 1 and 100')]
        public int    $limit = 10,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            groupBy: $request->query->get('group_by', 'day'),
            page: $request->query->getInt('page', 1),
            limit: $request->query->getInt('limit', 10),
        );
    }
}
