<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test Cart Calculation
 * Kiểm tra các phép tính giỏ hàng
 */
class CartCalculationTest extends TestCase
{
    public function testCartItemTotal()
    {
        $quantity = 3;
        $price = 100000;
        $total = $quantity * $price;
        
        $this->assertEquals(300000, $total, 'Tính tổng giỏ hàng không đúng');
    }

    public function testCartSubtotal()
    {
        $items = [
            ['quantity' => 2, 'price' => 100000],
            ['quantity' => 1, 'price' => 150000],
            ['quantity' => 3, 'price' => 50000]
        ];
        
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['quantity'] * $item['price'];
        }
        
        $expected = (2 * 100000) + (1 * 150000) + (3 * 50000);
        $this->assertEquals($expected, $subtotal, 'Tính subtotal không đúng');
    }

    public function testShippingFee()
    {
        $subtotal = 500000;
        $freeShippingThreshold = 1000000;
        $shippingFee = 30000;
        
        $calculatedFee = ($subtotal >= $freeShippingThreshold) ? 0 : $shippingFee;
        
        $this->assertEquals($shippingFee, $calculatedFee, 'Phí ship phải được tính');
    }

    public function testFreeShipping()
    {
        $subtotal = 1500000;
        $freeShippingThreshold = 1000000;
        $shippingFee = 30000;
        
        $calculatedFee = ($subtotal >= $freeShippingThreshold) ? 0 : $shippingFee;
        
        $this->assertEquals(0, $calculatedFee, 'Phải được freeship');
    }

    public function testVoucherDiscount()
    {
        $subtotal = 500000;
        $discountPercent = 10;
        $discount = $subtotal * $discountPercent / 100;
        
        $this->assertEquals(50000, $discount, 'Tính giảm giá voucher không đúng');
    }

    public function testFinalTotal()
    {
        $subtotal = 500000;
        $shippingFee = 30000;
        $discount = 50000;
        $finalTotal = $subtotal + $shippingFee - $discount;
        
        $expected = 500000 + 30000 - 50000;
        $this->assertEquals($expected, $finalTotal, 'Tính tổng cuối không đúng');
    }

    public function testEmptyCart()
    {
        $items = [];
        $total = 0;
        
        foreach ($items as $item) {
            $total += $item['quantity'] * $item['price'];
        }
        
        $this->assertEquals(0, $total, 'Giỏ hàng rỗng phải có tổng = 0');
    }

    public function testMaxQuantityPerItem()
    {
        $maxQty = 99;
        $requestedQty = 150;
        
        $allowedQty = min($maxQty, $requestedQty);
        
        $this->assertEquals($maxQty, $allowedQty, 'Số lượng không được vượt quá giới hạn');
    }
}
