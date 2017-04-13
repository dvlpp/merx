<?php

use Dvlpp\Merx\Exceptions\MapperException;
use Dvlpp\Merx\Models\Cart;
use Dvlpp\Merx\Models\CartItem;


/**
 * @return Cart|null
 */
function merx_current_cart()
{
    if (!config("merx.uses_session", true)) {
        // If Merx can't use the session, there is no way
        // he could find the current Cart from nowhere
        return null;
    }

    $cartId = session("merx_cart_id");

    if (!$cartId) {
        return null;
    }

    return Cart::find($cartId);
}

/**
 * @return int|null
 */
function merx_current_client_id()
{
    if(!config("merx.uses_authenticated_clients", true)) {
        // Merx is configured to handle clients manually
        return session("merx_client_id");
    }

    $user = app('auth')->user();

    if ($user && (!method_exists($user, "isMerxUser") || $user->isMerxUser())) {
        return $user->id;
    }

    return null;
}