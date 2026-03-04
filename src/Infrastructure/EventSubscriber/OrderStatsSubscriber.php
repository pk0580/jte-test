<?php

namespace App\Infrastructure\EventSubscriber;

use App\Domain\Entity\Order;
use App\Domain\Entity\OrderStats;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::postPersist, priority: 500, connection: 'default')]
readonly class OrderStatsSubscriber
{
    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof Order) {
            return;
        }

        $em = $args->getObjectManager();
        $date = $entity->getCreateDate();
        $amount = (string)$entity->getTotalAmount();

        $periods = [
            'day' => $date->format('Y-m-d'),
            'month' => $date->format('Y-m'),
            'year' => $date->format('Y'),
        ];

        foreach ($periods as $groupBy => $period) {
            $this->updateStats($em, $period, $groupBy, $amount);
        }
    }

    private function updateStats(EntityManagerInterface $em, string $period, string $groupBy, string $amount): void
    {
        $stats = $em->getRepository(OrderStats::class)->findOneBy([
            'period' => $period,
            'groupBy' => $groupBy
        ]);

        if (!$stats) {
            $stats = new OrderStats($period, $groupBy);
            $em->persist($stats);
        }

        $stats->addOrder($amount);
    }
}
