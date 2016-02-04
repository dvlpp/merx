# Merx

Merx is a shopping cart package for Laravel 5.2+.
It takes care of the cart and its items, and also the final order,
providing models and migrations.

## Configuration

### Install with composer

<code>composer require dvlpp/merx</code>

### Add Merx Service Provider

Add the following line in the <code>providers</code> section
of your <code>config/app.php</code> file:

<code>\Dvlpp\Merx\MerxServiceProvider::class</code>

You can add the alias for the Façade, too:

<code>'Merx' => Dvlpp\Merx\Facade\Merx::class</code>

### Set Merx config

First create the merx config file and the migrations files:

<code>php artisan vendor:publish --provider="Dvlpp\Merx\MerxServiceProvider"</code>

And then edit the new <code>/config/merx.php</code>. Here's the available options list:

    "users" => [
        "table" => "users",
        "eloquent_model" => \App\User::class
    ],

If this option is set before the actual migration, Merx will add a foreign key constraint
in the <code>merx_orders</code> table on the <code>client_id</code> attribute, and
<code>$order->client()</code> will return a belongsTo() relationship.

    "item_mapper" => App\MerxItemMapper::class,

If set, the related class must define a mapCartItemAttributes($object) function
which purpose is to convert a project model object to a merx
cart item, returning and array with these keys:

- name: the item name
- price: the item price, in cents
- details: item details (optional)
- id: the model object id
- type: the model object full class name


    "order_ref_generator" => "date-and-day-increment",

How the Order unique reference should be generated. Built-in options are
increment, date-and-increment, date-and-day-increment. To implement
a custom generator, type here the full path of a class which implements
<code>Dvlpp\Merx\Utils\OrderRefGenerator\OrderRefGenerator</code> interface.

### Migrate DB schema

Run <code>php artisan migrate</code> to add the 3 Merx tables:

- merx_carts
- merx_cart_items
- merx_orders

## Usage

Following examples use the Façade, but if you're anything like me,
you can of course use directly the Dvlpp\Merx\Merx class with DI.

### Get the current cart or create one

To get the instance of the current cart, type:

<code>Merx::cart()</code>

If there's no cart instance in the session, it will be created, and
persisted in the DB merx_carts table.

### Add an item to the cart

Then to add an item in the cart, you can write:

    Merx::cart()->addItem([
        "article_id" => 1
        "article_type" => App\Article::class
        "name" => 'some tshirt'
        "details" => 'the blue one'
        "price" => 1990 // Note that prices are in cents everywhere in MErx
        "quantity" => 1
    ]);

Or, if you wrote and configure some MerxItemMapper class (see config):

    $tshirt = App\Article::find(1);
    Merx::cart()->addItem($tshirt, 1);

If the article was already in the cart (same article_id), quantity would add up.

### Remove an item from the cart

Well...

    Merx::cart()->removeItem($item);

$item can either be:

- the <code>article_id</code>
- an instance of the related article (an <code>App\Article</code> in our example)
- an instance of <code>CartItem</code>

To clear the cart, you can call:

    Merx::cart()->emptyCart();

### And the rest of it

    $tshirt = App\Article::find(1);
    Merx::cart()->addItem($tshirt, 1);
    Merx::cart()->updateItemQuantity($shirt, 2);
    Merx::cart()->itemsCount(); // 2
    Merx::cart()->total(); // 2000 if each tshirt is $10
    Merx::cart()->findItem(1); // $tshirt CartItem object

### Create an order

You can create an order from the cart; it will persist a row
in the merx_orders table:

    Merx::newOrderFromCart();

This action will throw an exception if:

- there's no cart
- the cart is empty
- there's no current authentified client

## License

[WTFPL](https://en.wikipedia.org/wiki/WTFPL)
