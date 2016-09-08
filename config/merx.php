<?php

return [

    // Optional user config, for the user <-> order foreign key
    "users" => [
        "table" => "users",
        "eloquent_model" => \App\User::class
    ],

    // Item mapper must define a mapCartItemAttributes($object) function
    // which purpose is to convert a project model object to a merx
    // cart item, returning and array with these keys:
    // name: the item name
    // price: the item price, in cents
    // details: item details (optional)
    // id: the model object id
    // type: the model object full class name
//    "item_mapper" => App\MerxItemMapper::class,

    // How the Order reference should be generated. Built-in options are
    // increment, date-and-increment, date-and-day-increment. To implement
    // a custom generator, type here the full path of a class which implements
    // Dvlpp\Merx\Utils\OrderRefGenerator\OrderRefGenerator interface
    "order_ref_generator" => "date-and-day-increment",

    // The class to use as CartItem in merx
    "cart_item_class" => \Dvlpp\Merx\Models\CartItem::class,

    // Indicate if we should store the cart in session
    "uses_session" => true,

];