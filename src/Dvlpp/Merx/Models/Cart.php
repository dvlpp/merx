<?php

namespace Dvlpp\Merx\Models;

use Dvlpp\Merx\Exceptions\CartItemNotFoundException;
use Dvlpp\Merx\Exceptions\InvalidCartItemException;
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

//        Cart::creating(function ($cart) {
//            $cart->state = "opened";
//        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany(CartItem::class, "cart_id");
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function order()
    {
        return $this->hasOne(Order::class, "cart_id");
    }

    /**
     * @param array|object $attributes
     * @param int|null $quantity
     * @return static
     * @throws CartClosedException
     * @throws InvalidCartItemException
     * @throws MapperException
     */
    public function addItem($attributes, $quantity = null)
    {
        if (!$this->isOpened()) {
            throw new CartClosedException();
        }

        // We first consider that $attributes is an instance of CartItem
        $item = $attributes;

        if (is_array($attributes)) {
            $item = new CartItem($attributes);

        } elseif (is_object($attributes) && !$attributes instanceof CartItem) {
            // Mapping case: we try to insert a "domain" object
            $item = merx_item_map($attributes);
        }

        if (!$item || !$item instanceof CartItem) {
            throw new InvalidCartItemException;
        }

        if ($quantity) {
            $item->quantity = $quantity;

        } elseif ($item->quantity == 0) {
            $item->quantity = 1;
        }

        // If item already exist, we add up quantities
        $existingItem = $this->findItem($item->article_id);
        if ($existingItem) {
            $existingItem->quantity += $item->quantity;
            $existingItem->save();

            return $existingItem;
        }

        return $this->items()->save($item);
    }

    /**
     * @param string|CartItem|Object $itemRef
     * @return $this
     */
    public function removeItem($itemRef)
    {
        $removableItem = $this->getItemFromRefOrItemOrObject($itemRef);

        foreach ($this->items as $key => $cartItem) {
            if ($cartItem->article_id != $removableItem->article_id) {
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
    public function findItem($ref)
    {
        return $this->items()
            ->where("article_id", $ref)
            ->first();
    }

    /**
     * @param int $id
     * @return CartItem|null
     */
    public function getItem($id)
    {
        return $this->items()->find($id);
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
     * @throws CartItemNotFoundException
     */
    public function updateItemQuantity($itemRef, $quantity)
    {
        $updatableItem = $this->getItemFromRefOrItemOrObject($itemRef);

        if (!$updatableItem) {
            throw new CartItemNotFoundException;
        }

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
        return $this->order == null
        || $this->order->state != "completed";
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return count($this->items) == 0;
    }

    /**
     * @param string|CartItem|Object $itemRef
     * @return CartItem|null
     * @throws MapperException
     */
    protected function getItemFromRefOrItemOrObject($itemRef)
    {
        if ($itemRef instanceof CartItem) {
            return $itemRef;
        }

        if (is_object($itemRef)) {
            $item = merx_item_map($itemRef);
            $itemRef = $item->article_id;
        }

        return $this->findItem($itemRef);
    }
}