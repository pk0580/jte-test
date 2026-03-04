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
         <clientName>John</clientName>
         <clientSurname>Doe</clientSurname>
         <email>invalid-email</email>
         <payType>1</payType>
         <articles>
            <item>
               <articleId>101</articleId>
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
        $this->assertStringContainsString('clientName', $content);
        $this->assertStringContainsString('clientSurname', $content);
    }

    public function testCreateOrderWithNonExistentArticle(): void
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
               <articleId>999999</articleId>
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
        $this->assertStringContainsString('articleId', $content);
        $this->assertStringContainsString('does not exist', $content);
    }

    public function testCreateOrderWithInvalidPayType(): void
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
         <payType>999999</payType>
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
        $content = $client->getResponse()->getContent();
        $this->assertStringContainsString('Validation failed', $content);
        $this->assertStringContainsString('payType', $content);
        $this->assertStringContainsString('Invalid payment type', $content);
    }
}
