<?php

namespace Tests\UI;

use PHPUnit\Framework\TestCase;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

/**
 * End-to-End Test: Complete Shopping Flow
 * Test toàn bộ quy trình mua hàng từ đầu đến cuối như người dùng thật
 * Flow: Trang chủ → Sản phẩm → Chi tiết → Chọn options → Giỏ hàng → Thanh toán
 */
class E2EShoppingFlowTest extends TestCase
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
            $this->markTestSkipped('Selenium Server khong chay: ' . $e->getMessage());
        }
    }

    /**
     * E2E Test: Quy trình mua hàng hoàn chỉnh trong 1 session
     * Chi tiết → Chọn màu/size → Thêm giỏ hàng → Xem giỏ hàng → Thanh toán → Đặt hàng
     * @test
     */
    public function user_can_complete_full_shopping_journey()
    {
        echo "\n========== E2E TEST: COMPLETE SHOPPING JOURNEY ==========\n";
        
        // BƯỚC 1: Vào trang chi tiết sản phẩm
        echo "\n[STEP 1] Navigate to product detail page...\n";
        $this->driver->get($this->baseUrl . '/index.php?pg=detail&id=1');
        $this->driver->wait(10)->until(
            WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::tagName('body'))
        );
        sleep(3);
        
        // BƯỚC 2: Chọn màu sắc (nếu có)
        echo "\n[STEP 2] Select color option...\n";
        try {
            $colorOptions = $this->driver->findElements(WebDriverBy::cssSelector('.detail-circle'));
            if (count($colorOptions) > 1) {
                $this->driver->executeScript('arguments[0].scrollIntoView({block: "center"});', [$colorOptions[1]]);
                sleep(1);
                $colorOptions[1]->click();
                sleep(2);
                echo "✓ Color selected\n";
            }
        } catch (\Exception $e) {
            echo "- No color options\n";
        }
        
        // BƯỚC 3: Chọn size (nếu có)
        echo "\n[STEP 3] Select size option...\n";
        try {
            $sizeOptions = $this->driver->findElements(WebDriverBy::cssSelector('.detail-size__item'));
            if (count($sizeOptions) > 0) {
                $this->driver->executeScript('arguments[0].scrollIntoView({block: "center"});', [$sizeOptions[0]]);
                sleep(1);
                $sizeOptions[0]->click();
                sleep(2);
                echo "✓ Size selected\n";
            }
        } catch (\Exception $e) {
            echo "- No size options\n";
        }
        
        // BƯỚC 4: Thêm vào giỏ hàng
        echo "\n[STEP 4] Add product to cart...\n";
        $addButtons = $this->driver->findElements(WebDriverBy::cssSelector('button.detail-button__cart, button[name="addtocart"]'));
        
        $buttonClicked = false;
        foreach ($addButtons as $btn) {
            if ($btn->isDisplayed()) {
                $this->driver->executeScript('arguments[0].scrollIntoView({block: "center"});', [$btn]);
                sleep(2);
                $this->driver->executeScript('arguments[0].click();', [$btn]);
                sleep(4);
                echo "✓ Added to cart\n";
                $buttonClicked = true;
                break;
            }
        }
        
        $this->assertTrue($buttonClicked, 'Must be able to add product to cart');
        
        // BƯỚC 5: Hệ thống tự động chuyển đến trang giỏ hàng
        echo "\n[STEP 5] Auto-redirected to cart page...\n";
        sleep(2);
        $currentUrl = $this->driver->getCurrentURL();
        $this->assertStringContainsString('cart', $currentUrl, 'Should auto-redirect to cart page');
        echo "✓ On cart page\n";
        
        // BƯỚC 6: Xem giỏ hàng và tìm nút "Tiếp tục thanh toán"
        echo "\n[STEP 6] View cart and find checkout button...\n";
        $this->driver->executeScript('window.scrollBy(0, 400);');
        sleep(3);
        
        // Tìm nút "Tiếp tục thanh toán"
        $buttons = $this->driver->findElements(WebDriverBy::tagName('button'));
        $checkoutButton = null;
        
        foreach ($buttons as $btn) {
            try {
                $text = $this->removeVietnameseAccents(strtolower(trim($btn->getText())));
                if (strpos($text, 'tiep tuc thanh toan') !== false) {
                    $checkoutButton = $btn;
                    echo "✓ Found checkout button\n";
                    break;
                }
            } catch (\Exception $e) {
                // Skip
            }
        }
        
        $this->assertNotNull($checkoutButton, 'Must have checkout button on cart page');
        
        // BƯỚC 7: Click nút "Tiếp tục thanh toán"
        echo "\n[STEP 7] Click checkout button...\n";
        $this->driver->executeScript('arguments[0].scrollIntoView({block: "center"});', [$checkoutButton]);
        sleep(2);
        $this->driver->executeScript('arguments[0].click();', [$checkoutButton]);
        sleep(4);
        
        $currentUrl = $this->driver->getCurrentURL();
        $this->assertStringContainsString('checkout', $currentUrl, 'Should navigate to checkout page');
        echo "✓ On checkout page\n";
        
        // BƯỚC 8: Điền form thanh toán (chỉ điền thông tin người đặt)
        echo "\n[STEP 8] Fill checkout form...\n";
        
        $formData = [
            'tendat' => 'Nguyen Van A',
            'emaildat' => 'test@example.com',
            'sdtdat' => '0987654321',
            'diachidat' => '123 Nguyen Hue, Q1, TPHCM'
        ];
        
        $filledCount = 0;
        foreach ($formData as $fieldName => $value) {
            try {
                $input = $this->driver->findElement(WebDriverBy::name($fieldName));
                
                // Scroll vào view
                $this->driver->executeScript('arguments[0].scrollIntoView({block: "center"});', [$input]);
                sleep(1);
                
                // Điền giá trị
                $input->clear();
                $input->sendKeys($value);
                echo "  ✓ Filled '$fieldName': $value\n";
                $filledCount++;
                sleep(1);
                
            } catch (\Exception $e) {
                echo "  - Could not fill '$fieldName': " . $e->getMessage() . "\n";
            }
        }
        
        $this->assertGreaterThan(0, $filledCount, 'Must fill at least one form field');
        echo "✓ Successfully filled $filledCount form fields\n";
        
        // BƯỚC 9: Cuộn xuống xem tổng đơn hàng
        echo "\n[STEP 9] Scroll to view order total...\n";
        $this->driver->executeScript('window.scrollBy(0, 400);');
        sleep(2);
        
        // BƯỚC 10: Tìm và click nút "Đặt hàng"
        echo "\n[STEP 10] Find and click 'Place Order' button...\n";
        $buttons = $this->driver->findElements(WebDriverBy::tagName('button'));
        
        $orderButton = null;
        foreach ($buttons as $btn) {
            try {
                $btnText = trim($btn->getText());
                $btnTextNoAccent = $this->removeVietnameseAccents(strtolower($btnText));
                
                if (strpos($btnTextNoAccent, 'dat hang') !== false) {
                    $orderButton = $btn;
                    echo "✓ Found 'Dat hang' button\n";
                    break;
                }
            } catch (\Exception $e) {
                // Skip
            }
        }
        
        $this->assertNotNull($orderButton, 'Must have order button on checkout page');
        
        // Click nút "Đặt hàng"
        $this->driver->executeScript('arguments[0].scrollIntoView({block: "center"});', [$orderButton]);
        sleep(2);
        $this->driver->executeScript('arguments[0].click();', [$orderButton]);
        sleep(3);
        echo "✓ Clicked 'Place Order' button\n";
        
        // Kiểm tra vẫn ở trang checkout (hoặc có thể redirect sang trang success)
        $finalUrl = $this->driver->getCurrentURL();
        $this->assertTrue(
            strpos($finalUrl, 'checkout') !== false || strpos($finalUrl, 'success') !== false,
            'Should stay on checkout or redirect to success page'
        );
        
        echo "\n========== E2E TEST COMPLETED SUCCESSFULLY ==========\n";
    }

    protected function tearDown(): void
    {
        if ($this->driver) {
            $this->driver->quit();
        }
    }

    /**
     * Helper: Loại bỏ dấu tiếng Việt để so sánh text
     */
    private function removeVietnameseAccents($str)
    {
        $unicode = array(
            'a' => 'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',
            'd' => 'đ|Đ',
            'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
            'i' => 'í|ì|ỉ|ĩ|ị',
            'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
            'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
            'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
        );
        
        foreach ($unicode as $nonAccent => $accent) {
            $str = preg_replace("/($accent)/i", $nonAccent, $str);
        }
        
        return $str;
    }
}
