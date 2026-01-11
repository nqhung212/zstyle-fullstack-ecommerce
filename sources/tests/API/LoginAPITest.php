<?php

namespace Tests\API;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Test Login API
 * Test các chức năng đăng nhập qua HTTP
 */
class LoginAPITest extends TestCase
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

    public function testHomePageIsAccessible()
    {
        $response = $this->client->get('/index.php');
        $statusCode = $response->getStatusCode();
        
        $this->assertEquals(200, $statusCode, 'Trang chủ phải trả về status 200');
    }

    public function testLoginPageIsAccessible()
    {
        $response = $this->client->get('/index.php?pg=login');
        $statusCode = $response->getStatusCode();
        
        $this->assertEquals(200, $statusCode, 'Trang login phải trả về status 200');
    }

    public function testRegisterPageIsAccessible()
    {
        $response = $this->client->get('/index.php?pg=register');
        $statusCode = $response->getStatusCode();
        
        $this->assertEquals(200, $statusCode, 'Trang register phải trả về status 200');
    }

    public function testProductPageIsAccessible()
    {
        $response = $this->client->get('/index.php?pg=product');
        $statusCode = $response->getStatusCode();
        
        $this->assertEquals(200, $statusCode, 'Trang product phải trả về status 200');
    }

    public function testCartPageIsAccessible()
    {
        $response = $this->client->get('/index.php?pg=cart');
        $statusCode = $response->getStatusCode();
        
        $this->assertEquals(200, $statusCode, 'Trang cart phải trả về status 200');
    }

    public function testContactPageIsAccessible()
    {
        $response = $this->client->get('/index.php?pg=contact');
        $statusCode = $response->getStatusCode();
        
        $this->assertEquals(200, $statusCode, 'Trang contact phải trả về status 200');
    }

    public function testAboutPageIsAccessible()
    {
        $response = $this->client->get('/index.php?pg=about');
        $statusCode = $response->getStatusCode();
        
        $this->assertEquals(200, $statusCode, 'Trang about phải trả về status 200');
    }

    public function testNewsPageIsAccessible()
    {
        $response = $this->client->get('/index.php?pg=news');
        $statusCode = $response->getStatusCode();
        
        $this->assertEquals(200, $statusCode, 'Trang news phải trả về status 200');
    }

    public function testCheckoutPageIsAccessible()
    {
        $response = $this->client->get('/index.php?pg=checkout');
        $statusCode = $response->getStatusCode();
        
        $this->assertEquals(200, $statusCode, 'Trang checkout phải trả về status 200');
    }

    public function testDesignPageIsAccessible()
    {
        $response = $this->client->get('/index.php?pg=design');
        $statusCode = $response->getStatusCode();
        
        $this->assertEquals(200, $statusCode, 'Trang design phải trả về status 200');
    }

    protected function tearDown(): void
    {
        $this->client = null;
    }
}
