<?php

namespace App\Domain\Entity;

use App\Infrastructure\Persistence\Doctrine\Repository\DoctrineOrderStatsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctrineOrderStatsRepository::class)]
#[ORM\Table(name: 'order_stats')]
#[ORM\Index(columns: ['period', 'group_by'], name: 'idx_stats_period_group')]
class OrderStats
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private string $period; // e.g., "2024-01-01", "2024-01", "2024"

    #[ORM\Column(length: 10)]
    private string $groupBy; // "day", "month", "year"

    #[ORM\Column]
    private int $orderCount = 0;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2)]
    private string $totalAmount = '0.00';

    public function __construct(string $period, string $groupBy)
    {
        $this->period = $period;
        $this->groupBy = $groupBy;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPeriod(): string
    {
        return $this->period;
    }

    public function getGroupBy(): string
    {
        return $this->groupBy;
    }

    public function getOrderCount(): int
    {
        return $this->orderCount;
    }

    public function setOrderCount(int $orderCount): self
    {
        $this->orderCount = $orderCount;
        return $this;
    }

    public function getTotalAmount(): string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(string $totalAmount): self
    {
        $this->totalAmount = $totalAmount;
        return $this;
    }

}
