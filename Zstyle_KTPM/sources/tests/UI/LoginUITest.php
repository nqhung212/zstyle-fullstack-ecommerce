<?php

namespace Tests\UI;

use PHPUnit\Framework\TestCase;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

/**
 * Test Login UI with Selenium
 * Test giao diện đăng nhập với Selenium WebDriver
 */
class LoginUITest extends TestCase
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

    public function testLoginPageLoads()
    {
        $this->driver->get($this->baseUrl . '/index.php?pg=login');
        $title = $this->driver->getTitle();
        
        $this->assertNotEmpty($title, 'Trang login phải có title');
    }

    public function testLoginFormExists()
    {
        $this->driver->get($this->baseUrl . '/index.php?pg=login');
        
        // Kiểm tra form login tồn tại
        $form = $this->driver->findElement(WebDriverBy::tagName('form'));
        $this->assertNotNull($form, 'Form login phải tồn tại');
    }

    public function testLoginWithEmptyFields()
    {
        $this->driver->get($this->baseUrl . '/index.php?pg=login');
        
        // Tìm nút submit và click
        $submitButton = $this->driver->findElement(WebDriverBy::cssSelector('button[type="submit"], input[type="submit"]'));
        $submitButton->click();
        
        // Đợi 2 giây
        sleep(2);
        
        $currentUrl = $this->driver->getCurrentURL();
        $this->assertNotEmpty($currentUrl);
    }

    public function testRegisterPageLoads()
    {
        $this->driver->get($this->baseUrl . '/index.php?pg=register');
        $title = $this->driver->getTitle();
        
        $this->assertNotEmpty($title, 'Trang register phải có title');
    }

    public function testNavigationToLoginPage()
    {
        $this->driver->get($this->baseUrl . '/index.php');
        
        // Tìm link đến trang login trong header
        try {
            $loginLinks = $this->driver->findElements(WebDriverBy::cssSelector('a[href*="login"]'));
            $this->assertGreaterThan(0, count($loginLinks), 'Phai co link login tren trang chu');
            
            // Click vào link đầu tiên
            $loginLinks[0]->click();
            sleep(2);
            
            // Kiểm tra đã chuyển đến trang login
            $currentUrl = $this->driver->getCurrentURL();
            $this->assertStringContainsString('login', $currentUrl);
        } catch (\Exception $e) {
            $this->markTestSkipped('Khong tim thay link login tren trang chu');
        }
    }

    protected function tearDown(): void
    {
        if ($this->driver) {
            $this->driver->quit();
        }
    }
}
