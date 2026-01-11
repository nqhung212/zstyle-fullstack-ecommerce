<?php

namespace Tests\API;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

/**
 * Test Product API
 * Test các chức năng liên quan đến sản phẩm
 */
class ProductAPITest extends TestCase
{
    private $client;
    private $baseUrl;

    protected function setUp(): void
    {
        $this->baseUrl = getenv('APP_URL') ?: 'http://localhost:8080';
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'http_errors' => false,
            'cookies' => true,
            'verify' => false
        ]);
    }

    public function testProductListPageLoads()
    {
        $response = $this->client->get('/index.php?pg=product');
        
        $this->assertEquals(200, $response->getStatusCode());
        $body = (string) $response->getBody();
        $this->assertStringContainsString('product', strtolower($body));
    }

    public function testProductDetailPageWithValidId()
    {
        $response = $this->client->get('/index.php?pg=detail&id=1');
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testSearchFunctionality()
    {
        $response = $this->client->get('/index.php?pg=product&keyword=áo');
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCatalogFilter()
    {
        $response = $this->client->get('/index.php?pg=product&idcatalog=1');
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testResponseTimeIsAcceptable()
    {
        $start = microtime(true);
        $response = $this->client->get('/index.php');
        $duration = microtime(true) - $start;
        
        $this->assertLessThan(2, $duration, 'Response time phải < 2 giây');
    }

    public function testContentTypeIsHTML()
    {
        $response = $this->client->get('/index.php');
        $contentType = $response->getHeaderLine('Content-Type');
        
        $this->assertStringContainsString('text/html', $contentType);
    }

    protected function tearDown(): void
    {
        $this->client = null;
    }
}
