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
use Illuminate\Http\Request;

class Merx
{
    /**
     * @var Cart
     */
    private $cart;

    /**
     * @var \Illuminate\Http\Request
     */
    private $request

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Returns the current session's Cart, or create a new one.
     *
     * @param  integer $cartId
     * 
     * @return Cart
     */
    public function cart($cartId = null)
    {
        if (!$this->cart) {
            $this->cart = $this->getCartOrCreateNew($cartId);
        }

        return $this->cart;
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
            $this->request->session()->forget("merx_cart_id");
        }

        return $order;
    }

    /**
     * Get an existing Cart
     * 
     * @return Cart
     */
    private function getExistingCart($cartId)
    {
        return Cart::whereId($cartId)->first();
    }

    /**
     * @return Cart
     */
    private function getCartOrCreateNew($cartId = null)
    {
        if($cartId)
        {
            $cart = $this->getExistingCart($cartId);
        }

        $cart = merx_current_cart();

        if (!$cart) {
            $cart = Cart::create();

            if(config("merx.uses_session", true)) {
                $this->request->session()->put("merx_cart_id", $cart->id);
            }
        }

        return $cart;
    }

}
