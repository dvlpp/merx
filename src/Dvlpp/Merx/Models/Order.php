<?php

namespace Dvlpp\Merx\Models;

use Illuminate\Database\Eloquent\Model;
use Dvlpp\Merx\Exceptions\EmptyCartException;
use Dvlpp\Merx\Exceptions\CartClosedException;
use Dvlpp\Merx\Exceptions\NoCurrentCartException;
use Dvlpp\Merx\Exceptions\NoCurrentClientException;
use Dvlpp\Merx\Exceptions\OrderWithThisRefAlreadyExist;

class Order extends Model
{
    protected $table = "merx_orders";

    protected $fillable = [
        "ref",
        "state",
        "attributes"
    ];

    /**
     * Assign cart_id and client_id to the new order
     */
    public static function boot()
    {
        parent::boot();

        Order::saving(function ($order) {
            $cart = merx_current_cart();
            $clientId = merx_current_client_id();

            if (!$clientId) {
                throw new NoCurrentClientException();
            }

            static::checkCartIsValid($cart);

            $existingOrder = Order::where("ref", $order->ref)
                ->first();

            if ($existingOrder) {
                throw new OrderWithThisRefAlreadyExist();
            }

            $cart->close();

            $order->cart_id = $cart->id;
            $order->client_id = $clientId;
            $order->state = "draft";
        });
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class, "cart_id");
    }

    public function client()
    {
        if (config("merx.users.eloquent_model")) {
            return $this->belongsTo(
                config("merx.users.eloquent_model"),
                "client_id"
            );
        }

        return null;
    }

    public function items()
    {
        return $this->cart->items;
    }

    /**
     * @param $ref
     * @return CartItem|null
     */
    public function item($ref)
    {
        return $this->cart->item($ref);
    }

    /**
     * @return int
     */
    public function total()
    {
        return $this->cart->total();
    }

    /**
     * @param Cart $cart
     * @throws CartClosedException
     * @throws EmptyCartException
     * @throws NoCurrentCartException
     */
    private static function checkCartIsValid($cart)
    {
        if (!$cart) {
            throw new NoCurrentCartException();
        }

        if (!$cart->isOpened()) {
            throw new CartClosedException();
        }

        if ($cart->isEmpty()) {
            throw new EmptyCartException();
        }
    }
}