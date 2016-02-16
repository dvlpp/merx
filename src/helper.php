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
    $user = app('auth')->user();

    if ($user && (!method_exists($user, "isMerxUser") || $user->isMerxUser())) {
        return $user->id;
    }

    return null;
}