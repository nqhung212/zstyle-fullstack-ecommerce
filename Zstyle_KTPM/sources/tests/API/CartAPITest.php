<?php

namespace Tests\API;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

/**
 * Test Cart API
 * Test các chức năng giỏ hàng
 */
class CartAPITest extends TestCase
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

    public function testCartPageIsAccessible()
    {
        $response = $this->client->get('/index.php?pg=cart');
        
        $this->assertEquals(200, $response->getStatusCode());
        $body = (string) $response->getBody();
        $this->assertStringContainsString('cart', strtolower($body));
    }

    public function testCheckoutPageIsAccessible()
    {
        $response = $this->client->get('/index.php?pg=checkout');
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCartViewAjax()
    {
        $response = $this->client->get('/ajax/giohangview.html');
        
        $statusCode = $response->getStatusCode();
        $this->assertContains($statusCode, [200, 404], 'Cart AJAX view phải trả về 200 hoặc 404');
    }

    public function testCheckoutWithProduct()
    {
        $response = $this->client->get('/index.php?pg=checkout&id=1');
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    protected function tearDown(): void
    {
        $this->client = null;
    }
}
