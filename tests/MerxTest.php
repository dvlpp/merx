<?php

use Dvlpp\Merx\Merx;
use Dvlpp\Merx\Models\Cart;
use Dvlpp\Merx\Models\Client;
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
            "session_id" => session()->getId()
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

        $merx->loginClient("123");

        $merx->newOrderFromCart("123");

        $this->assertNull(session("merx_cart_id"));
    }

    /** @test */
    public function we_can_login_an_existing_client()
    {
        Client::create([
            "ref" => "123"
        ]);

        $merx = new Merx();
        $client = $merx->loginClient("123");

        $this->assertInstanceOf(Client::class, $client);
        $this->assertEquals($client->id, session("merx_client_id"));
    }

    /** @test */
    public function we_can_login_as_a_new_client()
    {
        $merx = new Merx();
        $client = $merx->loginClient("123");

        $this->assertInstanceOf(Client::class, $client);

        $this->assertEquals($client->id, session("merx_client_id"));

        $this->seeInDatabase('merx_clients', [
            "id" => $client->id,
            "ref" => "123"
        ]);
    }

    /** @test */
    public function we_cannot_login_as_a_new_client_if_creation_is_forbidden()
    {
        $merx = new Merx();
        $client = $merx->loginClient("123", false);

        $this->assertNull($client);

        $this->assertEquals(null, session("merx_client_id"));

        $this->dontSeeInDatabase('merx_clients', [
            "ref" => "123"
        ]);
    }

    /** @test */
    public function current_logged_client_is_returned()
    {
        $merx = new Merx();

        $client = $merx->loginClient("123");

        $this->assertSame($client, $merx->client());
    }

    /** @test */
    public function we_can_use_the_facade()
    {
        $client = MerxFacade::loginClient("123");
        $cart = MerxFacade::cart();

        $this->assertInstanceOf(Client::class, $client);
        $this->assertInstanceOf(Cart::class, $cart);
    }

}