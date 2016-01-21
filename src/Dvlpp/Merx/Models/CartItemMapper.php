<?php

namespace Dvlpp\Merx\Models;

interface CartItemMapper
{
    /**
     * @param $object
     * @return array
     */
    public function mapCartItemAttributes($object);
}