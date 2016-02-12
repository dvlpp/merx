<?php

namespace Dvlpp\Merx\Models;

use Illuminate\Database\Eloquent\Model;
use Dvlpp\Merx\Exceptions\MapperException;
use Dvlpp\Merx\Exceptions\CartClosedException;
use Dvlpp\Merx\Exceptions\InvalidCartItemException;
use Dvlpp\Merx\Exceptions\CartItemNotFoundException;

class Cart extends Model
{
    protected $table = "merx_carts";

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany($this->cartItemClass(), "cart_id");
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function order()
    {
        return $this->hasOne(Order::class, "cart_id");
    }

    /**
     * Add a CartItem to the Cart.
     *
     * @param $item
     * @param int|null $quantity
     * @return static
     * @throws CartClosedException
     * @throws InvalidCartItemException
     * @throws MapperException
     */
    public function addItem($item, $quantity = null)
    {
        if (!$this->isOpened()) {
            throw new CartClosedException();
        }

        if (!$item instanceof CartItem) {
            $item = $this->buildCartItem($item);

            if ($quantity) {
                $item->quantity = $quantity;

            } elseif ($item->quantity == 0) {
                $item->quantity = 1;
            }

            // If item already exist, we add up quantities
            $existingItem = $this->findItemWithSameArticle($item);
            if ($existingItem) {
                $existingItem->quantity += $item->quantity;
                $existingItem->save();

                return $existingItem;
            }
        }

        $savedItem = $this->items()->save($item);

        // If the relationship was already loaded, only saving
        // the related item won't update the model's collection,
        // we need explicitly push it so they stay in sync.
        if ($this->relationLoaded('items')) {
            $this->items->push($savedItem);
        }

        return $savedItem;
    }

    /**
     * Remove an item from the Cart
     *
     * @param int|CartItem $itemId
     * @return $this
     */
    public function removeItem($itemId)
    {
        $removableItem = $this->findItem($itemId);

        foreach ($this->items as $key => $cartItem) {
            if ($cartItem->article_id != $removableItem->article_id) {
                continue;
            }
            if (! $this->itemIsConsideredEqual($cartItem, $removableItem)) {
                continue;
            }

            $cartItem->delete();
            $this->items->forget($key);
            break;
        }

        return $this;
    }

    /**
     * @param int $itemId
     * @return CartItem|null
     */
    public function findItem($itemId)
    {
        if ($itemId instanceof CartItem) {
            $itemId = $itemId->id;
        }

        return $this->items()->find($itemId);
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
     * @param int|CartItem $itemId
     * @param int $quantity
     * @return $this
     * @throws CartItemNotFoundException
     */
    public function updateItemQuantity($itemId, $quantity)
    {
        $updatableItem = $this->findItem($itemId);

        if (!$updatableItem) {
            throw new CartItemNotFoundException;
        }

        $updatableItem->update([
            "quantity" => $quantity
        ]);

        // If the relationship was already loaded, we have
        // to explicitly update the item quantity
        if ($this->relationLoaded('items')) {
            $this->items->where("id", $updatableItem->id)
                ->first()->quantity = $quantity;
        }

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
        return $this->order == null || $this->order->state != "completed";
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return count($this->items) == 0;
    }

    /**
     * Map a CartItem from a domain object.
     *
     * @param $object
     * @return CartItem
     * @throws MapperException
     */
    private function mapCartItem($object)
    {
        $mapper = $this->newCartItemDomainMapperInstance();
        $attributes = $mapper->mapCartItemAttributes($object);

        $itemClass = $this->cartItemClass();
        $cartItem = $itemClass::newItemWith($attributes);

        return $cartItem;
    }

    /**
     * Build a CartItem object from an attributes array
     * or a domain object to be mapped.
     *
     * @param array|object $item
     * @return CartItem
     * @throws InvalidCartItemException
     * @throws MapperException
     */
    protected function buildCartItem($item)
    {
        if (is_array($item)) {
            // Attributes array case
            $itemClass = $this->cartItemClass();
            $item = $itemClass::newItemWith($item);

        } elseif (is_object($item)) {
            // Mapping case: we try to insert a "domain" object
            $item = $this->mapCartItem($item);
        }

        if (!$item instanceof CartItem) {
            throw new InvalidCartItemException;
        }

        return $item;
    }

    /**
     * @param CartItem $item
     * @return CartItem|null
     */
    private function findItemWithSameArticle(CartItem $item)
    {
        $items = $this->items()->where("article_id", $item->article_id)
            ->where("article_type", $item->article_type)
            ->get();

        foreach ($items as $sameItem) {
            // Array comparison on all attributes
            if ($this->itemIsConsideredEqual($item, $sameItem)) {
                return $sameItem;
            }
        }

        return null;
    }

    private function itemIsConsideredEqual($itemA, $itemB)
    {
        // Check if there's some custom attributes to ignore in
        // the item comparison
        $domainClass = $itemA->article_type;
        $attrToExcept = isset($domainClass::$merxCartItemAttributesExceptions)
            ? $domainClass::$merxCartItemAttributesExceptions
            : [];

        return ($itemA->allCustomAttributes()->except($attrToExcept)->toArray() == $itemB->allCustomAttributes()->except($attrToExcept)->toArray());
    }

    /**
     * @return mixed
     * @throws MapperException
     */
    private function newCartItemDomainMapperInstance()
    {
        $mapperClass = config("merx.item_mapper");

        if (!$mapperClass) {
            throw new MapperException("The merx.item_mapper config was not found.");
        }

        return new $mapperClass;
    }

    /**
     * @return mixed
     */
    private function cartItemClass()
    {
        return config('merx.cart_item_class', CartItem::class);
    }
}