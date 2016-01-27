<?php

use Dvlpp\Merx\Merx;
use Dvlpp\Merx\Models\Cart;
use Dvlpp\Merx\Facade\Merx as MerxFacade;

class MerxTest extends TestCase
{
    /** @test */
    public function a_new_cart_is_created_if_nothing_in_session()
    {
        $merx = new Merx();

        $cart = $merx->cart();

        $this->assertInstanceOf(\Dvlpp\Merx\Models\Cart::class, $cart);

        $this->seeInDatabase('merx_carts', [
            "id" => $cart->id,
        ]);
    }

    /** @test */
    public function same_cart_is_returned_if_already_created()
    {
        $merx = new Merx();

        $cart = $merx->cart();
        $cart2 = $merx->cart();

        $this->assertSame($cart, $cart2);
    }

    /** @test */
    public function we_can_create_a_new_order()
    {
        $merx = new Merx();

        $cart = $merx->cart();
        $cart->addItem($this->itemAttributes());

        $this->loginClient();

        $merx->newOrderFromCart("123");

        $this->assertNull(session("merx_cart_id"));
    }

    /** @test */
    public function we_can_use_the_facade()
    {
        $cart = MerxFacade::cart();

        $this->assertInstanceOf(Cart::class, $cart);
    }

}