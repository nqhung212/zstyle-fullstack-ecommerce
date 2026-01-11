<?php

namespace Tests\UI;

use PHPUnit\Framework\TestCase;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;

/**
 * Smoke UI Test
 * Kiểm tra nhanh những chức năng UI cơ bản
 * Dùng khi deploy để xác minh hệ thống "sống" (basic functionality works)
 */
class SmokeUITest extends TestCase
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
        } catch (\Exception $e) {
            $this->markTestSkipped('Selenium Server khong chay: ' . $e->getMessage());
        }
    }

    /**
     * Smoke Test: Trang chủ có load được không
     * @test
     */
    public function homepage_loads_successfully()
    {
        $this->driver->get($this->baseUrl . '/index.php');
        sleep(2);
        
        $title = $this->driver->getTitle();
        $this->assertNotEmpty($title, 'Homepage should have a title');
        
        $bodyText = $this->driver->findElement(WebDriverBy::tagName('body'))->getText();
        $this->assertNotEmpty($bodyText, 'Homepage should have content');
    }

    /**
     * Smoke Test: Trang sản phẩm có load được không
     * @test
     */
    public function product_page_loads_successfully()
    {
        $this->driver->get($this->baseUrl . '/index.php?pg=product');
        sleep(2);
        
        $bodyText = $this->driver->findElement(WebDriverBy::tagName('body'))->getText();
        $this->assertNotEmpty($bodyText, 'Product page should have content');
    }

    /**
     * Smoke Test: Trang giỏ hàng có load được không
     * @test
     */
    public function cart_page_loads_successfully()
    {
        $this->driver->get($this->baseUrl . '/index.php?pg=cart');
        sleep(2);
        
        $currentUrl = $this->driver->getCurrentURL();
        $this->assertStringContainsString('cart', $currentUrl, 'Should be on cart page');
    }

    /**
     * Smoke Test: Trang login có load được không
     * @test
     */
    public function login_page_loads_successfully()
    {
        $this->driver->get($this->baseUrl . '/index.php?pg=login');
        sleep(2);
        
        $currentUrl = $this->driver->getCurrentURL();
        $this->assertStringContainsString('login', $currentUrl, 'Should be on login page');
    }

    /**
     * Smoke Test: Có thể navigate giữa các trang không
     * @test
     */
    public function can_navigate_between_pages()
    {
        // Home
        $this->driver->get($this->baseUrl . '/index.php');
        sleep(1);
        $this->assertNotEmpty($this->driver->getTitle());
        
        // Product
        $this->driver->get($this->baseUrl . '/index.php?pg=product');
        sleep(1);
        $currentUrl = $this->driver->getCurrentURL();
        $this->assertStringContainsString('product', $currentUrl);
        
        // Cart
        $this->driver->get($this->baseUrl . '/index.php?pg=cart');
        sleep(1);
        $currentUrl = $this->driver->getCurrentURL();
        $this->assertStringContainsString('cart', $currentUrl);
    }

    protected function tearDown(): void
    {
        if ($this->driver) {
            $this->driver->quit();
        }
    }
}
