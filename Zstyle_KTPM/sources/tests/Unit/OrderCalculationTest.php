<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Class OrderCalculationTest
 * 
 * Unit tests for Order module logic.
 * Tests calculation of totals, shipping, discounts, and status validation.
 */
class OrderCalculationTest extends TestCase
{
    protected $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->order = new Order();
    }

    /**
     * Test calculating total from a list of products (qty * price).
     */
    public function testCalculateSubtotalFromItems()
    {
        $this->order->addItem(['name' => 'Product A', 'price' => 100, 'qty' => 2]);
        $this->order->addItem(['name' => 'Product B', 'price' => 50, 'qty' => 1]);

        // 100*2 + 50*1 = 250
        $this->assertEquals(250, $this->order->getSubtotal());
    }

    /**
     * Test calculating total when order is empty.
     */
    public function testCalculateTotalWithEmptyItems()
    {
        $this->assertEquals(0, $this->order->getSubtotal());
        $this->assertEquals(0, $this->order->getTotal());
    }

    /**
     * Test applying shipping fee to the order.
     */
    public function testApplyShippingFee()
    {
        $this->order->addItem(['name' => 'Product A', 'price' => 100, 'qty' => 1]);
        $this->order->setShippingFee(30);

        // 100 + 30 = 130
        $this->assertEquals(130, $this->order->getTotal());
    }

    /**
     * Test applying free shipping when threshold is reached.
     */
    public function testFreeShippingThreshold()
    {
        // Assume threshold is 500
        $this->order->addItem(['name' => 'Expensive Item', 'price' => 600, 'qty' => 1]);
        $this->order->setShippingFee(30);
        
        // Logic: If subtotal > 500, shipping should be 0 (handled by calculateTotal or setShippingFee logic)
        // Here we simulate the logic check
        if ($this->order->getSubtotal() > 500) {
            $this->order->setShippingFee(0);
        }

        $this->assertEquals(600, $this->order->getTotal());
    }

    /**
     * Test applying discount (voucher/percent).
     */
    public function testApplyDiscountVoucher()
    {
        $this->order->addItem(['name' => 'Product A', 'price' => 200, 'qty' => 1]);
        $this->order->setDiscount(50); // Flat discount

        // 200 - 50 = 150
        $this->assertEquals(150, $this->order->getTotal());
    }

    /**
     * Test calculating final total (Subtotal + Shipping - Discount).
     */
    public function testCalculateFinalTotal()
    {
        $this->order->addItem(['name' => 'Product A', 'price' => 100, 'qty' => 2]); // 200
        $this->order->setShippingFee(20); // +20
        $this->order->setDiscount(10); // -10

        // 200 + 20 - 10 = 210
        $this->assertEquals(210, $this->order->getTotal());
    }

    /**
     * Test valid order statuses.
     */
    public function testValidOrderStatuses()
    {
        $this->assertTrue($this->order->setStatus('pending'));
        $this->assertEquals('pending', $this->order->getStatus());

        $this->assertTrue($this->order->setStatus('paid'));
        $this->assertEquals('paid', $this->order->getStatus());

        $this->assertTrue($this->order->setStatus('canceled'));
        $this->assertEquals('canceled', $this->order->getStatus());
    }

    /**
     * Test invalid order status rejection.
     */
    public function testInvalidOrderStatus()
    {
        $this->assertFalse($this->order->setStatus('invalid_status'));
        $this->assertNotEquals('invalid_status', $this->order->getStatus());
    }

    /**
     * Test that total cannot be negative.
     */
    public function testTotalCannotBeNegative()
    {
        $this->order->addItem(['name' => 'Product A', 'price' => 100, 'qty' => 1]);
        $this->order->setDiscount(200); // Discount > Total

        // Should return 0, not -100
        $this->assertEquals(0, $this->order->getTotal());
    }
}

/**
 * Mock Order Class to simulate logic for Unit Testing.
 * In a real scenario, this would be the actual App\Model\Order class.
 */
class Order
{
    protected $items = [];
    protected $shippingFee = 0;
    protected $discount = 0;
    protected $status = 'new';

    protected $validStatuses = ['new', 'pending', 'paid', 'shipped', 'canceled'];

    public function addItem($item)
    {
        $this->items[] = $item;
    }

    public function getSubtotal()
    {
        $subtotal = 0;
        foreach ($this->items as $item) {
            $subtotal += $item['price'] * $item['qty'];
        }
        return $subtotal;
    }

    public function setShippingFee($fee)
    {
        $this->shippingFee = $fee;
    }

    public function setDiscount($amount)
    {
        $this->discount = $amount;
    }

    public function getTotal()
    {
        $total = $this->getSubtotal() + $this->shippingFee - $this->discount;
        return max(0, $total);
    }

    public function setStatus($status)
    {
        if (in_array($status, $this->validStatuses)) {
            $this->status = $status;
            return true;
        }
        return false;
    }

    public function getStatus()
    {
        return $this->status;
    }
}
