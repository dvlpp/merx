<?php

namespace Dvlpp\Merx\Utils\OrderRefGenerator;

use Dvlpp\Merx\Models\Order;

class DateAndDayIncrement extends Increment
{

    /**
     * Generate a unique ref for a new Order.
     *
     * @return string
     */
    function generate()
    {
        $today = date("Ymd");

        $order = Order::where("ref", "like", "$today-%")
            ->orderBy("ref", "desc")
            ->first();

        if (! $order) {
            return "$today-001";
        }

        list($date, $increment) = explode("-", $order->ref);

        $existing = true;
        while($existing) {
            $increment = str_pad(((int)$increment+1), 3, '0', STR_PAD_LEFT);
            $existing = Order::where("ref", "$today-$increment")
                ->first();
        }

        return "$today-$increment";
    }
}