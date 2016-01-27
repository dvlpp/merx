<?php

namespace Dvlpp\Merx;

use Dvlpp\Merx\Models\Cart;
use Dvlpp\Merx\Models\Order;
use Dvlpp\Merx\Exceptions\EmptyCartException;
use Dvlpp\Merx\Exceptions\CartClosedException;
use Dvlpp\Merx\Exceptions\NoCurrentCartException;
use Dvlpp\Merx\Exceptions\NoCurrentClientException;
use Dvlpp\Merx\Exceptions\OrderWithThisRefAlreadyExist;

class Merx
{
    /**
     * @var Cart
     */
    private $cart;

    /**
     * Returns the current session's Cart, or create a new one.
     *
     * @return Cart
     */
    public function cart()
    {
        if (!$this->cart) {
            $this->cart = $this->getCartOrCreateNew();
        }

        return $this->cart;
    }

    /**
     * @param string $ref
     * @return Order
     */
    public function order($ref)
    {
        return Order::where("ref", $ref)->first();
    }

    /**
     * Store a new order based on the session's cart and client.
     *
     * @param string $orderRef
     * @return Order
     *
     * @throws NoCurrentClientException
     * @throws CartClosedException
     * @throws EmptyCartException
     * @throws NoCurrentCartException
     * @throws OrderWithThisRefAlreadyExist
     */
    public function newOrderFromCart($orderRef)
    {
        // Create order from session's cart
        $order = Order::create([
            "ref" => $orderRef,
        ]);

        // Remove cart from session
        session()->forget("merx_cart_id");

        return $order;
    }

    /**
     * @return Cart
     */
    private function getCartOrCreateNew()
    {
        $cart = merx_current_cart();

        if (!$cart) {
            $cart = Cart::create();
            session()->put("merx_cart_id", $cart->id);
        }

        return $cart;
    }

}