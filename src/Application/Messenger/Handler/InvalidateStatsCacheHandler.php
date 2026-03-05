<?php

namespace App\Application\Messenger\Handler;

use App\Application\Messenger\Message\InvalidateStatsCacheMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsMessageHandler]
class InvalidateStatsCacheHandler
{
    public function __construct(
        private readonly TagAwareCacheInterface $statsCache
    ) {}

    public function __invoke(InvalidateStatsCacheMessage $message): void
    {
        $this->statsCache->invalidateTags(['stats']);
    }
}
