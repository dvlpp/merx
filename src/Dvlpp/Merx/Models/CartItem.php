<?php

namespace Dvlpp\Merx\Models;

use Dvlpp\Merx\Exceptions\InvalidCartItemException;
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
        "custom_attributes"
    ];

    protected $casts = [
        'custom_attributes' => 'array',
    ];

    public static function newItemWith(array $attributes)
    {
        $validator = validator($attributes, [
            "article_id" => "required",
            "article_type" => "required",
            "name" => "required",
            "price" => "required|int",
            "quantity" => "int",
            "attributes" => "array"
        ]);

        if ($validator->fails()) {
            throw new InvalidCartItemException;
        }

        $item = new static($attributes);

        if (isset($attributes["attributes"])) {
            foreach ($attributes["attributes"] as $attribute => $value) {
                $item->setCustomAttribute($attribute, $value, false);
            }
        }

        return $item;
    }

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