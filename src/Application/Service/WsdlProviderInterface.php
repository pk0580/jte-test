<?php

namespace App\Application\Service;

interface WsdlProviderInterface
{
    public function getWsdlPath(): string;
    public function getWsdlContent(): string;
}
