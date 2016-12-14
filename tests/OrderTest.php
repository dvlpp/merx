<?php

use Dvlpp\Merx\Exceptions\EmptyCartException;
use Dvlpp\Merx\Exceptions\NoCurrentCartException;
use Dvlpp\Merx\Exceptions\NoCurrentClientException;
use Dvlpp\Merx\Exceptions\OrderWithThisRefAlreadyExist;
use Dvlpp\Merx\Models\Cart;
use Dvlpp\Merx\Models\CartItem;
use Dvlpp\Merx\Models\Order;

class OrderTest extends TestCase
{

    /** @test */
    public function we_can_make_a_new_order()
    {
        $cart = Cart::create();
        session()->put("merx_cart_id", $cart->id);

        $client = $this->loginClient();

        $cart->addItem(new CartItem($this->itemAttributes()));

        $order = Order::create([
            "ref" => "123"
        ]);

        $this->seeInDatabase('merx_orders', [
            "id" => $order->id,
            "ref" => "123",
            "cart_id" => $cart->id,
            "client_id" => $client->id
        ]);
    }

    /** @test */
    public function we_can_make_a_new_order_with_an_empty_cart()
    {
        $cart = Cart::create();
        session()->put("merx_cart_id", $cart->id);

        $client = $this->loginClient();

        $order = Order::create([
            "ref" => "123"
        ]);

        $this->seeInDatabase('merx_orders', [
            "id" => $order->id,
            "ref" => "123",
            "cart_id" => $cart->id,
            "client_id" => $client->id
        ]);
    }

    /** @test */
    public function we_cant_make_a_new_order_without_a_client()
    {
        $cart = Cart::create();
        session()->put("merx_cart_id", $cart->id);

        $this->setExpectedException(NoCurrentClientException::class);

        Order::create([
            "ref" => "123"
        ]);
    }

    /** @test */
    public function we_cant_make_a_new_order_without_a_cart()
    {
        $this->loginClient();

        $this->setExpectedException(NoCurrentCartException::class);

        Order::create([
            "ref" => "123"
        ]);
    }

    /** @test */
    public function we_cant_create_an_order_with_an_existing_ref()
    {
        $this->loginClient();

        $this->setExpectedException(OrderWithThisRefAlreadyExist::class);

        for ($k = 0; $k < 2; $k++) {
            $cart = Cart::create();
            session()->put("merx_cart_id", $cart->id);

            $cart->addItem(new CartItem($this->itemAttributes()));

            Order::create([
                "ref" => "aaa"
            ]);
        }
    }

    /** @test */
    public function we_cant_complete_an_order_with_an_empty_cart()
    {
        $cart = Cart::create();
        session()->put("merx_cart_id", $cart->id);

        $this->loginClient();

        $order = Order::create([
            "ref" => "aaa"
        ]);

        $this->setExpectedException(EmptyCartException::class);

        $order->complete();
    }

    /** @test */
    public function we_cant_use_the_increment_ref_generator()
    {
        $this->app['config']->set('merx.order_ref_generator', 'increment');

        $this->createCartAndClient();

        $order1 = Order::create();
        $order2 = Order::create();

        $this->seeInDatabase('merx_orders', [
            "id" => $order1->id,
            "ref" => "1"
        ]);

        $this->seeInDatabase('merx_orders', [
            "id" => $order2->id,
            "ref" => "2"
        ]);
    }

    /** @test */
    public function we_cant_use_the_date_and_increment_ref_generator()
    {
        $this->app['config']->set('merx.order_ref_generator', 'date-and-increment');

        $this->createCartAndClient();

        // Simulate a yesterday order
        Order::create([
            "ref" => date("Ymd", time() - 24 * 60 * 60) . "-1"
        ]);
        $order1 = Order::create();
        $order2 = Order::create();

        $this->seeInDatabase('merx_orders', [
            "id" => $order1->id,
            "ref" => date("Ymd") . "-002"
        ]);

        $this->seeInDatabase('merx_orders', [
            "id" => $order2->id,
            "ref" => date("Ymd") . "-003"
        ]);
    }

    /** @test */
    public function we_cant_use_the_date_and_day_increment_ref_generator()
    {
        $this->app['config']->set('merx.order_ref_generator', 'date-and-day-increment');

        $this->createCartAndClient();

        // Simulate a yesterday order
        Order::create([
            "ref" => date("Ymd", time() - 24 * 60 * 60) . "-1"
        ]);
        $order1 = Order::create();
        $order2 = Order::create();

        $this->seeInDatabase('merx_orders', [
            "id" => $order1->id,
            "ref" => date("Ymd") . "-001"
        ]);

        $this->seeInDatabase('merx_orders', [
            "id" => $order2->id,
            "ref" => date("Ymd") . "-002"
        ]);
    }

    /** @test */
    public function we_cant_use_a_custom_ref_generator()
    {
        $this->app['config']->set('merx.order_ref_generator', CustomOrderRefGenerator::class);

        $this->createCartAndClient();

        Order::create();
        Order::create();
        Order::create();
    }

    /** @test */
    public function we_can_add_custom_attribute_to_an_order()
    {
        $this->createCartAndClient();

        $order = Order::create([
            "ref" => "123"
        ]);

        $order->setCustomAttribute("custom", "value");

        $this->assertEquals("value", $order->customAttribute("custom"));

        $this->seeInDatabase('merx_orders', [
            "id" => $order->id,
            "custom_attributes" => json_encode([
                "custom" => "value"
            ])
        ]);
    }

    /** @test */
    public function we_can_add_multiple_custom_attributes_at_once_to_an_order()
    {
        $this->createCartAndClient();

        $order = Order::create([
            "ref" => "123"
        ]);

        $order->setMultipleCustomAttributes([
            "custom" => "value",
            "custom2" => "value2",
        ]);

        $this->assertEquals("value", $order->customAttribute("custom"));
        $this->assertEquals("value2", $order->customAttribute("custom2"));

        $this->seeInDatabase('merx_orders', [
            "id" => $order->id,
            "custom_attributes" => json_encode([
                "custom" => "value",
                "custom2" => "value2"
            ])
        ]);
    }

    /** @test */
    public function we_can_remove_a_custom_attribute_from_an_order()
    {
        $this->createCartAndClient();

        $order = Order::create([
            "ref" => "123"
        ]);

        $order->setCustomAttribute("custom", "value");

        $this->assertEquals("value", $order->customAttribute("custom"));

        $order->removeCustomAttribute("custom");

        $this->assertEquals(null, $order->customAttribute("custom"));

        $this->seeInDatabase('merx_orders', [
            "id" => $order->id,
            "custom_attributes" => json_encode([])
        ]);
    }

    /** @test */
    public function we_can_remove_multiple_custom_attributes_at_once_from_an_order()
    {
        $this->createCartAndClient();

        $order = Order::create([
            "ref" => "123"
        ]);

        $order->setCustomAttribute("custom", "value");
        $order->setCustomAttribute("custom2", "value");

        $this->assertEquals("value", $order->customAttribute("custom"));
        $this->assertEquals("value", $order->customAttribute("custom2"));

        $order->removeMultipleCustomAttributes(["custom", "custom2"]);

        $this->assertEquals(null, $order->customAttribute("custom"));
        $this->assertEquals(null, $order->customAttribute("custom2"));

        $this->seeInDatabase('merx_orders', [
            "id" => $order->id,
            "custom_attributes" => json_encode([])
        ]);
    }

    protected function createCartAndClient()
    {
        $cart = Cart::create();
        session()->put("merx_cart_id", $cart->id);

        $cart->addItem(new CartItem($this->itemAttributes()));

        $this->loginClient();
    }
}

class CustomOrderRefGenerator implements \Dvlpp\Merx\Utils\OrderRefGenerator\OrderRefGenerator
{

    /**
     * Generate a unique ref for a new Order.
     *
     * @return string
     */
    function generate()
    {
        return uniqid();
    }
}