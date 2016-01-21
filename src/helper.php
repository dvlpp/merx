<?php

use Dvlpp\Merx\Exceptions\MapperException;
use Dvlpp\Merx\Models\Cart;
use Dvlpp\Merx\Models\CartItem;
use Dvlpp\Merx\Models\Client;


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
        ->where("session_id", session()->getId())
        ->first();
}

/**
 * @return Client|null
 */
function merx_current_client()
{
    $clientId = session("merx_client_id");

    if (!$clientId) {
        return null;
    }

    return Client::where("id", $clientId)
        ->first();
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

    return new CartItem($mapper->mapCartItemAttributes($object));
}