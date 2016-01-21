<?php

namespace Dvlpp\Merx\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $table = "merx_cart_items";

    protected $fillable = [
        "ref",
        "name",
        "details",
        "price",
        "quantity"
    ];

    /**
     * @return int
     */
    public function subtotal()
    {
        return $this->price * $this->quantity;
    }
}