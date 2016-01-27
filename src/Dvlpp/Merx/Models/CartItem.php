<?php

namespace Dvlpp\Merx\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use WithCustomAttributes;

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

    protected $casts = [
        'custom_attributes' => 'array',
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