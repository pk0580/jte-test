<?php

namespace App\Controller\Api\v1;

use App\Application\Service\SoapOrderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

class SoapController extends AbstractController
{
    #[Route('/soap', name: 'soap_endpoint', methods: ['GET', 'POST'])]
    public function index(Request $request, SoapOrderService $soapOrderService): Response
    {
        $wsdlPath = $this->getParameter('kernel.project_dir') . '/public/wsdl/order.wsdl';

        if ($request->isMethod('GET')) {
            if (!file_exists($wsdlPath)) {
                return new Response('WSDL file not found', 404);
            }
            return new Response(file_get_contents($wsdlPath), 200, ['Content-Type' => 'text/xml']);
        }

        $soapServer = new \SoapServer($wsdlPath, [
            'cache_wsdl' => WSDL_CACHE_NONE,
        ]);

        $soapServer->setObject($soapOrderService);

        ob_start();
        try {
            $soapServer->handle($request->getContent());
        } catch (Throwable $e) {
            ob_end_clean(); // Очищаем буфер, так как там может быть мусор от SoapServer
            $soapServer->fault('Receiver', $e->getMessage());
        }
        $content = ob_get_clean();

        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml; charset=utf-8');
        $response->setContent($content);

        return $response;
    }
}
