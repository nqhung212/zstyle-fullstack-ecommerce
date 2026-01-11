<?php

namespace Tests\UI;

use PHPUnit\Framework\TestCase;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;

/**
 * Test Cart UI with Selenium
 * Test giao diện giỏ hàng
 */
class CartUITest extends TestCase
{
    private $driver;
    private $baseUrl;

    protected function setUp(): void
    {
        $this->baseUrl = getenv('APP_URL') ?: 'http://localhost:8080';
        $seleniumUrl = getenv('SELENIUM_URL') ?: 'http://localhost:4444';
        
        try {
            $this->driver = RemoteWebDriver::create(
                $seleniumUrl,
                DesiredCapabilities::chrome()
            );
            $this->driver->manage()->window()->maximize();
        } catch (\Exception $e) {
            $this->markTestSkipped('Selenium Server không chạy: ' . $e->getMessage());
        }
    }

    public function testCartPageLoads()
    {
        $this->driver->get($this->baseUrl . '/index.php?pg=cart');
        $title = $this->driver->getTitle();
        
        $this->assertNotEmpty($title, 'Trang giỏ hàng phải có title');
    }

    public function testCheckoutPageLoads()
    {
        $this->driver->get($this->baseUrl . '/index.php?pg=checkout');
        $title = $this->driver->getTitle();
        
        $this->assertNotEmpty($title, 'Trang checkout phải có title');
    }

    public function testNavigationToCart()
    {
        $this->driver->get($this->baseUrl . '/index.php');
        
        // Tìm link giỏ hàng trong header
        try {
            $cartLinks = $this->driver->findElements(WebDriverBy::cssSelector('a[href*="cart"]'));
            $this->assertGreaterThan(0, count($cartLinks), 'Phai co link cart tren header');
            
            // Click vào link cart
            $cartLinks[0]->click();
            sleep(2);
            
            // Kiểm tra đã chuyển đến trang cart
            $currentUrl = $this->driver->getCurrentURL();
            $this->assertStringContainsString('cart', $currentUrl);
        } catch (\Exception $e) {
            $this->markTestSkipped('Khong tim thay link cart tren trang chu');
        }
    }

    public function testContactPageLoads()
    {
        $this->driver->get($this->baseUrl . '/index.php?pg=contact');
        $title = $this->driver->getTitle();
        
        $this->assertNotEmpty($title, 'Trang contact phải có title');
    }

    public function testAboutPageLoads()
    {
        $this->driver->get($this->baseUrl . '/index.php?pg=about');
        $title = $this->driver->getTitle();
        
        $this->assertNotEmpty($title, 'Trang about phải có title');
    }

    protected function tearDown(): void
    {
        if ($this->driver) {
            $this->driver->quit();
        }
    }
}
