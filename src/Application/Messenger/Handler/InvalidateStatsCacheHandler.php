<?php

namespace App\Application\Messenger\Handler;

use App\Application\Messenger\Message\InvalidateStatsCacheMessage;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsMessageHandler]
readonly class InvalidateStatsCacheHandler
{
    public function __construct(
        private TagAwareCacheInterface $statsCache
    ) {}

    /**
     * @throws InvalidArgumentException
     */
    public function __invoke(InvalidateStatsCacheMessage $message): void
    {
        $this->statsCache->invalidateTags(['stats']);
    }
}
