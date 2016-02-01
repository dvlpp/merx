<?php

namespace Dvlpp\Merx\Utils\OrderRefGenerator;

use Dvlpp\Merx\Models\Order;

class DateAndIncrement extends Increment
{

    /**
     * Generate a unique ref for a new Order.
     *
     * @return string
     */
    function generate()
    {
        $today = date("Ymd");
        $order = Order::orderBy("ref", "desc")
            ->first();

        if (!$order) {
            return "$today-1";
        }

        list($date, $increment) = explode("-", $order->ref);

        $increment++;

        return "$today-$increment";
    }
}