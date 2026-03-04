<?php

namespace App\Tests\Controller\Soap;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SoapControllerTest extends WebTestCase
{
    public function testCreateOrderSoap(): void
    {
        $client = static::createClient();

        $xml = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:soap="http://localhost:8000/soap">
   <soapenv:Header/>
   <soapenv:Body>
      <soap:CreateOrderRequest>
         <clientName>John</clientName>
         <clientSurname>Doe</clientSurname>
         <email>john.doe@example.com</email>
         <payType>1</payType>
         <articles>
            <item>
               <articleId>1</articleId>
               <amount>2.5</amount>
               <price>100.00</price>
               <weight>1.2</weight>
            </item>
         </articles>
      </soap:CreateOrderRequest>
   </soapenv:Body>
</soapenv:Envelope>
XML;

        $client->request(
            'POST',
            '/soap',
            [],
            [],
            ['CONTENT_TYPE' => 'text/xml', 'HTTP_SOAPAction' => 'createOrder'],
            $xml
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('text/xml', $client->getResponse()->headers->get('Content-Type'));
        $this->assertStringContainsString('<success>true</success>', $client->getResponse()->getContent());
        $this->assertStringContainsString('<orderId>', $client->getResponse()->getContent());
    }

    public function testGetWsdl(): void
    {
        $client = static::createClient();
        $client->request('GET', '/soap');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('text/xml', $client->getResponse()->headers->get('Content-Type'));
        $this->assertStringContainsString('<definitions', $client->getResponse()->getContent());
    }
}
