<?php

namespace Dvlpp\Merx\Models;

use Illuminate\Database\Eloquent\Model;
use Dvlpp\Merx\Exceptions\MapperException;
use Dvlpp\Merx\Exceptions\CartClosedException;

class Cart extends Model
{
    protected $table = "merx_carts";

    /**
     * Assign session_id to new cart
     */
    public static function boot()
    {
        parent::boot();

        Cart::creating(function ($cart) {
            $cart->state = "opened";
        });

        Cart::saving(function ($cart) {
            $cart->session_id = session()->getId();
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany(CartItem::class, "cart_id");
    }

    /**
     * @param array|object $attributes
     * @param int|null $quantity
     * @return static
     * @throws CartClosedException
     * @throws MapperException
     */
    public function addItem($attributes, $quantity = null)
    {
        if (!$this->isOpened()) {
            throw new CartClosedException();
        }

        if (is_array($attributes)) {
            $item = new CartItem($attributes);

        } elseif ($attributes instanceof CartItem) {
            $item = $attributes;

        } elseif (is_object($attributes)) {
            $item = merx_item_map($attributes);
        }

        if ($quantity) {
            $item->quantity = $quantity;
        }

        return $this->items()->save($item);
    }

    /**
     * @param string|CartItem|Object $itemRef
     * @return $this
     */
    public function removeItem($itemRef)
    {
        $removableItem = $this->getItem($itemRef);

        foreach ($this->items as $key => $cartItem) {
            if ($cartItem->ref != $removableItem->ref) {
                continue;
            }

            $cartItem->delete();
            $this->items->forget($key);
            break;
        }

        return $this;
    }

    /**
     * @param $ref
     * @return CartItem|null
     */
    public function item($ref)
    {
        return $this->items->where("ref", $ref)->first();
    }

    /**
     * @return int
     */
    public function total()
    {
        return $this->items->reduce(function ($carry, $item) {
            return $carry + $item->subtotal();
        });
    }

    /**
     * @param string|CartItem|Object $itemRef
     * @param int $quantity
     * @return $this
     */
    public function updateItemQuantity($itemRef, $quantity)
    {
        $updatableItem = $this->getItem($itemRef);

        $updatableItem->update([
            "quantity" => $quantity
        ]);

        return $this;
    }

    /**
     * @return int
     */
    public function itemsCount()
    {
        return $this->items->reduce(function ($carry, $item) {
            return $carry + $item->quantity;
        });
    }

    /**
     * @return int
     */
    public function emptyCart()
    {
        $this->items()->delete();

        return $this;
    }

    /**
     * @return bool
     */
    public function isOpened()
    {
        return $this->state == "opened";
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return count($this->items) == 0;
    }

    /**
     * Close the cart.
     */
    public function close()
    {
        $this->state = "closed";
        $this->save();
    }

    /**
     * @param string|CartItem|Object $itemRef
     * @return CartItem|null
     * @throws MapperException
     */
    protected function getItem($itemRef)
    {
        if ($itemRef instanceof CartItem) {
            return $itemRef;
        }

        if (is_object($itemRef)) {
            $item = merx_item_map($itemRef);
            $itemRef = $item->ref;
        }

        return $this->item($itemRef);
    }
}