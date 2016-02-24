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
     * @param integer|null $cartId
     * @param bool $createIfNeeded
     * @return Cart
     */
    public function cart($cartId = null, $createIfNeeded = true)
    {
        if (!$this->cart) {
            $this->cart = $this->existingCart($cartId);

            if (!$this->cart && $createIfNeeded) {
                $this->cart = $this->newCart();
            }
        }

        return $this->cart;
    }

    public function hasCart()
    {
        if ($this->cart) {
            return true;
        }

        return $this->existingCart() != null;
    }

    /**
     * @param integer $cartId
     * 
     * @return Order
     */
    public function order($cartId = null)
    {
        return $this->cart($cartId)->order;
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

        if(config("merx.uses_session", true)) {
            session()->forget("merx_cart_id");
        }

        return $order;
    }

    /**
     * @param integer|null $cartId
     * @return Cart|null
     */
    private function existingCart($cartId = null)
    {
        return $cartId
            ? Cart::whereId($cartId)->first()
            : merx_current_cart();
    }

    /**
     * @return Cart
     */
    private function newCart()
    {
        $cart = Cart::create();

        if (config("merx.uses_session", true)) {
            session()->put("merx_cart_id", $cart->id);
        }

        return $cart;
    }
}