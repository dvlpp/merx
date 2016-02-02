<?php

namespace Dvlpp\Merx;

use Dvlpp\Merx\Exceptions\NoCurrentOrderException;
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
     * @return Order
     */
    public function order()
    {
        return $this->cart()->order;
    }

    /**
     * Store a new order based on the session's cart and client.
     *
     * @param string|null $orderRef
     * @return Order
     *
     * @throws NoCurrentClientException
     * @throws CartClosedException
     * @throws EmptyCartException
     * @throws NoCurrentCartException
     * @throws OrderWithThisRefAlreadyExist
     */
    public function newOrderFromCart($orderRef = null)
    {
        // Create order from session's cart
        $order = $this->cart()->order()->create([
            "ref" => $orderRef
        ]);

        // Force cart DB refresh on next call to reflect this association
        $this->cart = null;

        return $order;
    }

    /**
     * Close the order, remove cart from session.
     *
     * @throws NoCurrentOrderException
     * @throws CartClosedException
     * @throws EmptyCartException
     * @throws NoCurrentCartException
     * @return Order
     */
    public function completeOrder()
    {
        $order = $this->order();

        if (!$order) {
            throw new NoCurrentOrderException;
        }

        $order->complete();

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