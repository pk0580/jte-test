<?php

namespace App\Infrastructure\Soap;

use App\Application\Service\WsdlProviderInterface;
use RuntimeException;

readonly class WsdlProvider implements WsdlProviderInterface
{
    public function __construct(
        private string $wsdlPath
    ) {}

    public function getWsdlPath(): string
    {
        return $this->wsdlPath;
    }

    public function getWsdlContent(): string
    {
        if (!file_exists($this->wsdlPath)) {
            throw new RuntimeException('WSDL file not found at: ' . $this->wsdlPath);
        }

        return file_get_contents($this->wsdlPath);
    }
}
