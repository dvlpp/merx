<?php

namespace Dvlpp\Merx;

use Dvlpp\Merx\Exceptions\MerxException;
use Dvlpp\Merx\Models\Cart;
use Dvlpp\Merx\Models\Order;
use Dvlpp\Merx\Exceptions\NoCurrentCartException;
use Illuminate\Database\Eloquent\Model;

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

    /**
     * @return bool
     */
    public function hasCart()
    {
        if ($this->cart) {
            return true;
        }

        return $this->existingCart() != null;
    }

    /**
     * Return the current Order, or null.
     *
     * @param integer $cartId
     * @return Order
     */
    public function order($cartId = null)
    {
        return $this->cart($cartId)->order;
    }

    /**
     * Return the current Client, or null.
     *
     * @return Model|int|null
     */
    public function client()
    {
        $clientId = merx_current_client_id();

        if(!$clientId) {
            return null;
        }

        if(config("merx.users.eloquent_model")) {
            return call_user_func(
                config("merx.users.eloquent_model") . "::findOrFail",
                $clientId
            );
        }

        return $clientId;
    }

    /**
     * Manually sets the client id
     * (only with uses_session and !uses_authenticated_clients)
     *
     * @param int $clientId
     * @return bool
     * @throws MerxException
     */
    public function setClientId($clientId)
    {
        if (config("merx.uses_session", true)
            && !config("merx.uses_authenticated_clients", true)) {
            session()->put("merx_client_id", $clientId);

            return true;
        }

        throw new MerxException("You can't manually set the clientId: uses_session must be true and uses_authenticated_clients must be false");
    }

    /**
     * Store a new order based on the session's cart and client.
     *
     * @param string|null $orderRef
     * @param null $cartId
     * @return Order
     * @throws NoCurrentCartException
     */
    public function newOrderFromCart($orderRef = null, $cartId = null)
    {
        $cart = $this->cart($cartId, false);

        if (!$cart) {
            throw new NoCurrentCartException();
        }

        // Create order from current cart
        $order = $cart->createNewOrder($orderRef);

        // Force cart DB refresh on next call to reflect this association
        $this->cart = null;

        return $order;
    }

    /**
     * Close the order, remove cart from session.
     *
     * @param null $cartId
     * @return Order
     */
    public function completeOrder($cartId = null)
    {
        $order = $this->cart($cartId, false)->completeOrder();

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
            ? Cart::find($cartId)
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