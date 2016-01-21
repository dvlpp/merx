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
        "ref"
    ];

    /**
     * Assign cart_id and client_id to the new order
     */
    public static function boot()
    {
        parent::boot();

        Order::saving(function ($order) {
            $cart = merx_current_cart();
            $client = merx_current_client();

            if (!$client) {
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
            $order->client_id = $client->id;
        });
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class, "cart_id");
    }

    public function client()
    {
        return $this->belongsTo(Client::class, "client_id");
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