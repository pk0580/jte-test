<?php

namespace App\Tests\Controller\Soap;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SoapValidationTest extends WebTestCase
{
    public function testCreateOrderWithInvalidEmail(): void
    {
        $client = static::createClient();

        $xml = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:soap="http://localhost:8000/soap">
   <soapenv:Header/>
   <soapenv:Body>
      <soap:CreateOrderRequest>
         <client_name>John</client_name>
         <client_surname>Doe</client_surname>
         <email>invalid-email</email>
         <pay_type>1</pay_type>
         <articles>
            <item>
               <article_id>101</article_id>
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
        $content = $client->getResponse()->getContent();
        $this->assertStringContainsString('Validation failed', $content);
        $this->assertStringContainsString('email', $content);
        $this->assertStringNotContainsString('<success>true</success>', $content);
    }

    public function testCreateOrderWithMissingFields(): void
    {
        $client = static::createClient();

        $xml = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:soap="http://localhost:8000/soap">
   <soapenv:Header/>
   <soapenv:Body>
      <soap:CreateOrderRequest>
         <email>john.doe@example.com</email>
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
        $content = $client->getResponse()->getContent();
        $this->assertStringContainsString('Validation failed', $content);
        $this->assertStringContainsString('client_name', $content);
        $this->assertStringContainsString('client_surname', $content);
    }
}
