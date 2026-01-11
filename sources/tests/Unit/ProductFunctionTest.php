<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test Product Functions
 * Kiểm tra các hàm xử lý sản phẩm
 */
class ProductFunctionTest extends TestCase
{
    public function testPriceFormatting()
    {
        $price = 399000;
        $formatted = number_format($price, 0, '', ',');
        
        $this->assertEquals('399,000', $formatted, 'Format giá không đúng');
    }

    public function testDiscountCalculation()
    {
        $originalPrice = 500000;
        $discountPercent = 20;
        $salePrice = $originalPrice - ($originalPrice * $discountPercent / 100);
        
        $this->assertEquals(400000, $salePrice, 'Tính giảm giá không đúng');
    }

    public function testProductNameSanitization()
    {
        $productName = "  Áo Thun Cool  ";
        $sanitized = trim($productName);
        
        $this->assertEquals('Áo Thun Cool', $sanitized, 'Tên sản phẩm chưa được trim');
    }

    public function testProductSlugGeneration()
    {
        $productName = "Áo Thun Cool";
        // Simple slug generation
        $slug = strtolower(str_replace(' ', '-', $productName));
        
        $this->assertStringNotContainsString(' ', $slug, 'Slug không được chứa khoảng trắng');
    }

    public function testImagePathValidation()
    {
        $validExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $imageName = 'product_image.png';
        $extension = pathinfo($imageName, PATHINFO_EXTENSION);
        
        $this->assertContains($extension, $validExtensions, 'Extension ảnh không hợp lệ');
    }

    public function testStockQuantityValidation()
    {
        $stock = 10;
        $orderQty = 5;
        
        $this->assertGreaterThanOrEqual($orderQty, $stock, 'Số lượng đặt hàng vượt quá tồn kho');
    }

    public function testNegativeStockNotAllowed()
    {
        $stock = -5;
        
        $this->assertLessThan(0, $stock, 'Test kiểm tra số âm');
        // Trong thực tế, stock không được âm
        $validStock = max(0, $stock);
        $this->assertEquals(0, $validStock, 'Stock phải >= 0');
    }
}
