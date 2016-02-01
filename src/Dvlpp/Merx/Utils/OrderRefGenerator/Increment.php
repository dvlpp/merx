<?php

namespace Dvlpp\Merx\Utils\OrderRefGenerator;

use Dvlpp\Merx\Models\Order;

class Increment implements OrderRefGenerator
{

    /**
     * Generate a unique ref for a new Order.
     *
     * @return string
     */
    function generate()
    {
        $order = Order::orderBy("ref", "desc")->first();

        return $order ? $order->ref + 1 : 1;
    }
}