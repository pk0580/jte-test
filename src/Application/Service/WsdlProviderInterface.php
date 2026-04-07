<?php

declare(strict_types=1);

namespace App\Application\Service;

interface WsdlProviderInterface
{
    public function getWsdlPath(): string;
    public function getWsdlContent(): string;
}
