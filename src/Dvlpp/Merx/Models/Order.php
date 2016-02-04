<?php

namespace Dvlpp\Merx\Models;

use Dvlpp\Merx\Utils\OrderRefGeneratorFactory;
use Illuminate\Database\Eloquent\Model;
use Dvlpp\Merx\Exceptions\EmptyCartException;
use Dvlpp\Merx\Exceptions\CartClosedException;
use Dvlpp\Merx\Exceptions\NoCurrentCartException;
use Dvlpp\Merx\Exceptions\NoCurrentClientException;
use Dvlpp\Merx\Exceptions\OrderWithThisRefAlreadyExist;

class Order extends Model
{
    use WithCustomAttributes;

    protected $table = "merx_orders";

    protected $fillable = [
        "ref",
        "state",
        "custom_attributes"
    ];

    protected $casts = [
        'custom_attributes' => 'array',
    ];

    /**
     * Assign cart_id and client_id to the new order
     */
    public static function boot()
    {
        parent::boot();

        Order::creating(function ($order) {
            $clientId = merx_current_client_id();

            if (!$clientId) {
                throw new NoCurrentClientException();
            }

            $cart = $order->cart;
            if (!$cart) {
                $cart = merx_current_cart();
                $order->cart_id = $cart ? $cart->id : null;
            }

            static::checkCartIsValid($cart);

            if (!$order->ref) {
                // Have to generate a unique ref
                $order->ref = static::generateRef();

            } elseif (Order::where("ref", $order->ref)->count()) {
                throw new OrderWithThisRefAlreadyExist();
            }

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
     * Generate a unique ref for the new Order.
     *
     * @return string
     */
    protected static function generateRef()
    {
        $generatorName = config("merx.order_ref_generator", "increment");

        $generator = OrderRefGeneratorFactory::create($generatorName);

        return $generator->generate();
    }

    /**
     * Complete the order: close it
     *
     * @throws CartClosedException
     * @throws EmptyCartException
     * @throws NoCurrentCartException
     * @return $this
     */
    public function complete()
    {
        $this->checkCartIsValid($this->cart);

        $this->state = "completed";
        $this->save();

        return $this;
    }

    /**
     * @param Cart $cart
     * @throws CartClosedException
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
    }
}