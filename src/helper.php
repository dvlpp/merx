<?php

use Dvlpp\Merx\Exceptions\MapperException;
use Dvlpp\Merx\Models\Cart;
use Dvlpp\Merx\Models\CartItem;


/**
 * @return Cart|null
 */
function merx_current_cart()
{
    $cartId = session("merx_cart_id");

    if (!$cartId) {
        return null;
    }

    return Cart::where("id", $cartId)
        ->first();
}

/**
 * @return int|null
 */
function merx_current_client_id()
{
    $user = auth()->user();

    if ($user && $user->isMerxUser()) {
        return $user->id;
    }

    return null;
}

/**
 * @param $object
 * @return \Dvlpp\Merx\Models\CartItem
 * @throws MapperException
 */
function merx_item_map($object)
{
    $mapperClass = config("merx.item_mapper");

    if (!$mapperClass) {
        throw new MapperException("The merx.item_mapper config was not found.");
    }

    $mapper = new $mapperClass;

    $attributes = $mapper->mapCartItemAttributes($object);
    // TODO add some attribute presence validation
    $attributes["article_id"] = $attributes["id"];
    $attributes["article_type"] = $attributes["type"];
    unset($attributes["id"], $attributes["type"]);

    return new CartItem($attributes);
}