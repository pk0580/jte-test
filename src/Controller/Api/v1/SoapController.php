<?php

declare(strict_types=1);

namespace App\Controller\Api\v1;

use App\Application\Service\SoapOrderService;
use App\Application\Service\WsdlProviderInterface;
use App\Infrastructure\Monitoring\TraceIdContext;
use App\Infrastructure\Soap\SoapServerFactory;
use Psr\Log\LoggerInterface;
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
        WsdlProviderInterface $wsdlProvider,
        LoggerInterface $logger,
        TraceIdContext $traceIdContext
    ): Response {
        if ($request->isMethod('GET')) {
            try {
                return new Response($wsdlProvider->getWsdlContent(), 200, ['Content-Type' => 'text/xml']);
            } catch (Throwable $e) {
                $logger->error('SOAP WSDL error: ' . $e->getMessage(), [
                    'trace_id' => $traceIdContext->getTraceId(),
                    'exception' => $e
                ]);
                return new Response($e->getMessage(), 404);
            }
        }

        $soapServer = $soapServerFactory->create($soapOrderService);

        try {
            ob_start();
            $soapServer->handle($request->getContent());
            $responseContent = ob_get_clean();

            return new Response($responseContent, 200, ['Content-Type' => 'text/xml; charset=utf-8']);
        } catch (Throwable $e) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            $logger->error('SOAP Handle error: ' . $e->getMessage(), [
                'trace_id' => $traceIdContext->getTraceId(),
                'request' => $request->getContent(),
                'exception' => $e
            ]);

            ob_start();
            $soapServer->fault('Receiver', $e->getMessage());
            $faultContent = ob_get_clean();

            return new Response($faultContent, 500, ['Content-Type' => 'text/xml; charset=utf-8']);
        }
    }
}
