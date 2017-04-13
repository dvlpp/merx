<?php

use Dvlpp\Merx\Exceptions\MerxException;
use Dvlpp\Merx\Merx;
use Dvlpp\Merx\Models\Cart;
use Dvlpp\Merx\Facade\Merx as MerxFacade;

class MerxTest extends BrowserKitCase
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
    public function no_cart_is_returned_id_create_param_is_false()
    {
        $merx = new Merx();

        $cart = $merx->cart(null, false);

        $this->assertNull($cart);
    }

    /** @test */
    public function has_cart_works()
    {
        $merx = new Merx();

        $this->assertFalse($merx->hasCart());

        $merx->cart();

        $this->assertTrue($merx->hasCart());
    }

    /** @test */
    public function we_can_create_a_new_order()
    {
        $merx = new Merx();

        $order = $this->createNewOrder($merx);

        $this->seeInDatabase('merx_orders', [
            "id" => $order->id,
        ]);
    }

    /** @test */
    public function we_can_get_the_current_order()
    {
        $merx = new Merx();

        $order = $this->createNewOrder($merx);

        $this->assertEquals($order->id, $merx->order()->id);
    }

    /** @test */
    public function we_can_complete_the_current_order()
    {
        $merx = new Merx();

        $order = $this->createNewOrder($merx);

        $merx->completeOrder();

        $this->seeInDatabase('merx_orders', [
            "id" => $order->id,
            "state" => "completed"
        ]);

        $this->assertNull(session("merx_cart_id"));
    }

    /** @test */
    public function we_can_get_the_current_client_with_auth_and_no_client_model()
    {
        $merx = new Merx();

        $client = $this->loginClient();

        $this->assertEquals(
            $client->id, $merx->client()
        );
    }

    /** @test */
    public function we_can_get_the_current_client_with_auth_and_client_model()
    {
        $this->app['config']->set('merx.users', [
            "table" => "users",
            "eloquent_model" => \App\User::class
        ]);

        $merx = new Merx();

        $client = $this->loginClient();

        $this->assertEquals(
            $client->id, $merx->client()->id
        );
    }

    /** @test */
    public function we_can_get_the_current_client_with_no_auth_and_no_client_model()
    {
        $this->app['config']->set('merx.uses_authenticated_clients', false);

        $merx = new Merx();

        $client = factory(\App\User::class)->create();

        $merx->setClientId($client->id);

        $this->assertEquals(
            $client->id, $merx->client()
        );
    }

    /** @test */
    public function we_can_get_the_current_client_with_no_auth_and_client_model()
    {
        $this->app['config']->set('merx.uses_authenticated_clients', false);

        $this->app['config']->set('merx.users', [
            "table" => "users",
            "eloquent_model" => \App\User::class
        ]);

        $merx = new Merx();

        $client = factory(\App\User::class)->create();

        $merx->setClientId($client->id);

        $this->assertEquals(
            $client->id, $merx->client()->id
        );
    }

    /** @test */
    function we_cant_manually_set_client_id_with_wrong_config()
    {
        $merx = new Merx();

        $this->setExpectedException(MerxException::class);
        $merx->setClientId(1);
    }

    /** @test */
    public function we_can_use_the_facade()
    {
        $cart = MerxFacade::cart();

        $this->assertInstanceOf(Cart::class, $cart);
    }

    /**
     * @param Merx $merx
     * @return \Dvlpp\Merx\Models\Order
     * @throws \Dvlpp\Merx\Exceptions\CartClosedException
     * @throws \Dvlpp\Merx\Exceptions\InvalidCartItemException
     */
    protected function createNewOrder(Merx $merx)
    {
        $cart = $merx->cart();
        $cart->addItem($this->itemAttributes());

        $this->loginClient();

        $order = $merx->newOrderFromCart("123", $cart->id);

        return $order;
    }

}

