<?php

namespace App\Infrastructure\Soap;

use SoapServer;

class SoapServerFactory
{
    public function __construct(
        private string $wsdlPath,
        private bool $cacheEnabled = false
    ) {}

    public function create(object $service): SoapServer
    {
        $options = [
            'cache_wsdl' => $this->cacheEnabled ? WSDL_CACHE_BOTH : WSDL_CACHE_NONE,
            'exceptions' => true,
        ];

        $soapServer = new SoapServer($this->wsdlPath, $options);
        $soapServer->setObject($service);

        return $soapServer;
    }
}
