<?php

namespace Dvlpp\Merx\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $table = "merx_cart_items";

    protected $fillable = [
        "article_id",
        "article_type",
        "name",
        "details",
        "price",
        "quantity",
        "attributes"
    ];

    /**
     * @return int
     */
    public function subtotal()
    {
        return $this->price * $this->quantity;
    }

    public function article()
    {
        return $this->morphTo();
    }
}