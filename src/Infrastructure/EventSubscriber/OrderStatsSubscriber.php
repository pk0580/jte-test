<?php

namespace App\Infrastructure\EventSubscriber;

use App\Domain\Entity\Order;
use App\Domain\Entity\OrderStats;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsDoctrineListener(event: Events::postPersist, priority: 500, connection: 'default')]
readonly class OrderStatsSubscriber
{
    public function __construct(
        private TagAwareCacheInterface $statsCache
    ) {}

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof Order) {
            return;
        }

        $em = $args->getObjectManager();
        $date = $entity->getDates()->createAt;
        $amount = (string)$entity->getPricing()->totalAmount;

        $periods = [
            'day' => $date->format('Y-m-d'),
            'month' => $date->format('Y-m'),
            'year' => $date->format('Y'),
        ];

        foreach ($periods as $groupBy => $period) {
            $this->updateStats($em, $period, $groupBy, $amount);
        }

        $this->statsCache->invalidateTags(['stats']);
    }

    private function updateStats(EntityManagerInterface $em, string $period, string $groupBy, string $amount): void
    {
        $repository = $em->getRepository(OrderStats::class);
        if ($repository instanceof \App\Domain\Repository\OrderStatsRepositoryInterface) {
            $repository->incrementStats($period, $groupBy, $amount);
        } else {
            // Fallback если репозиторий почему-то не тот (хотя должен быть тот)
            $stats = $repository->findOneBy([
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
}
