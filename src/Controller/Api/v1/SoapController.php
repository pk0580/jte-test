<?php

namespace App\Controller\Api\v1;

use App\Application\Service\SoapOrderService;
use App\Application\Service\WsdlProviderInterface;
use App\Infrastructure\Soap\SoapServerFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

class SoapController extends AbstractController
{
    #[Route('/soap', name: 'soap_endpoint', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        SoapOrderService $soapOrderService,
        SoapServerFactory $soapServerFactory,
        WsdlProviderInterface $wsdlProvider
    ): Response {
        if ($request->isMethod('GET')) {
            try {
                return new Response($wsdlProvider->getWsdlContent(), 200, ['Content-Type' => 'text/xml']);
            } catch (Throwable $e) {
                return new Response($e->getMessage(), 404);
            }
        }

        $soapServer = $soapServerFactory->create($soapOrderService);

        try {
            $soapServer->handle($request->getContent());
            return new Response('', 200, ['Content-Type' => 'text/xml; charset=utf-8']);
        } catch (Throwable $e) {
            $soapServer->fault('Receiver', $e->getMessage());
            return new Response('', 500, ['Content-Type' => 'text/xml; charset=utf-8']);
        }
    }
}
