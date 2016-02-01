<?php

namespace Dvlpp\Merx\Utils\OrderRefGenerator;

interface OrderRefGenerator
{
    /**
     * Generate a unique ref for a new Order.
     *
     * @return string
     */
    function generate();
}