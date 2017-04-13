# Merx

Merx is a shopping cart package for Laravel 5.2+.
It takes care of the cart and its items, and also the final order,
providing models and migrations.

General philosophy is:

- all carts should be in database;
- an order is just a completed cart;
- we can add items in a cart but can't complete an order without being authenticated;
- a cart item can be linked to an domain object (an article), but
its core attributes are duplicated (price, name, details) to prevent
association issues (price changed, removed article, ...).

Merx is fully tested.

## Configuration

### Install with composer

`composer require dvlpp/merx`

### Add Merx Service Provider

Add the following line in the <code>providers</code> section
of your `config/app.php` file:

`\Dvlpp\Merx\MerxServiceProvider::class`

You can add the alias for the Façade, too:

`'Merx' => Dvlpp\Merx\Facade\Merx::class`

### Set Merx config

First create the merx config file and the migrations files:

`php artisan vendor:publish --provider="Dvlpp\Merx\MerxServiceProvider"`

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

- `name`: the item name
- `price`: the item price, in cents
- `details`: item details (optional)
- `article_id`: the model object id
- `article_type`: the model object full class name

And `quantity`: the item quantity, which can be
missing if quantity is set at the <code>addItem()</code> state.

    "order_ref_generator" => "date-and-day-increment",

How the Order unique reference should be generated. Built-in options are
increment, date-and-increment, date-and-day-increment. To implement
a custom generator, type here the full path of a class which implements
`Dvlpp\Merx\Utils\OrderRefGenerator\OrderRefGenerator` interface.

    "cart_item_class" => \Dvlpp\Merx\Models\CartItem::class,

If you want to add some logic in the CartItem model, you can declare here
your own implementation, which **has to extend** Dvlpp\Merx\Models\CartItem.
  
    "uses_session" => true,

Merx keeps the current cart in the current session. If you don't want this
behaviour, you can disable it here, but be aware that `Merx::cart()` won't
be able to find your cart: you'll have to pass the `cart_id` to this call.

    "uses_authenticated_clients" => true,

By default, Merx will use the current Laravel authenticated user as the client.
If you want, you can disable this behaviour but you'll have to manually call
`Merx::setClientId()` before creating the Order. This config is compatible
with the eloquent user binding, but will need a session in order to work
(meaning `uses_session` must be set to true).

### Migrate DB schema

Run `php artisan migrate` to add the 3 Merx tables:

- merx_carts
- merx_cart_items
- merx_orders

**[Laravel 5.2 and 5.3 only]** Alternatively, you can run the `php artisan merx:migrate`
to migrate the db without adding the migration classes, as part of your
deployment process. This command **will not** erase existing tables,
except with the `--refresh` option.

### Adapt your User class (optional)

Finally, Merx may use the Laravel auth system, with the standard User class
(this isn't true if you set `uses_authenticated_clients` to false).
If you need to separate Merx Users from other, you can define a
`isMerxUser()` function in your User model. If not present,
Merx will assume it's always true.

## Usage

Following examples use the Façade, but if you're anything like me,
you can of course use directly the `Dvlpp\Merx\Merx` class with DI.

### Get the current cart or create one

To get the instance of the current cart, type:

    Merx::cart()

If there's no cart instance in the session, it will be created, and
persisted in the DB merx_carts table.

### Add an item to the cart

Then to add an item in the cart, you can write:

    Merx::cart()->addItem([
        "article_id" => 1,
        "article_type" => App\Article::class,
        "name" => 'some tshirt',
        "details" => 'the blue one',
        "price" => 1990, // Note that prices are in cents everywhere in Merx
        "quantity" => 1
    ]);

Or, if you wrote and configure some MerxItemMapper class (see config):

    $tshirt = App\Article::find(1);
    Merx::cart()->addItem($tshirt, 1);
    
Finally, you can add directly a `CartItem`, which is useful if you decided
to code your own implementation (see config):
    
    Merx::cart()->addItem($cartItem, 1);

Note that if the article was already in the cart (same article_id), 
quantity would add up.

### Remove an item from the cart

Well...

    Merx::cart()->removeItem($item);

`$item` can either be:

- an instance of <code>CartItem</code>
- the <code>CartItem</code> id

To clear the cart, you can call:

    Merx::cart()->emptyCart();

### And the rest of it

    $tshirt = App\Article::find(1);
    $item = Merx::cart()->addItem($tshirt, 1);
    Merx::cart()->updateItemQuantity($shirt, 2);
    Merx::cart()->itemsCount(); // 2
    Merx::cart()->items; // Eloquent Collection
    Merx::cart()->total(); // 2000 if each tshirt is $10
    Merx::cart()->findItem($item->id); // CartItem instance
    $item->subtotal(); // 2000 also
    $item->article; // App\Article instance ($tshirt)

### Create an order

You can create an order from the cart; it will persist a row
in the merx_orders table:

    Merx::newOrderFromCart();

This action will throw an exception if:

- there's no cart
- the cart is already closed (meaning that this order was completed before)
- or there's no current client (meaning either no authenticated user 
or no previous call to `Merx::setClientId`, depending on config)

The order unique reference will be generated given your config (see related doc).
You can also pass a reference at creation time:

    Merx::newOrderFromCart("123");

In this case Merx will check for existing references and eventually throw an Exception.

After order creation, you can access it with:

    Merx::order();

### Complete an order

Once an order is complete, you can close it:

    Merx::order()->complete();

This will throw exception if:

- there's no cart
- cart is empty
- cart is already closed

Once closed, an order is supposed to be kind of immutable.

### Work with an order

You can write:

    Merx::order()->items(); // Cart items (Collection)
    Merx::order()->item(1);
    Merx::order()->total();

Those are shortcuts for Cart functions.

### Custom attributes

What if you want to store some other information in the cart,
or even attached to an item? Let's say you need to add
a coupon code to the cart:

    Merx::cart()->setCustomAttribute("coupon", "ABC");
    Merx::cart()->customAttribute("coupon"); // ABC

The same is true with cart items:

    Merx::cart()->findItem(1)->setCustomAttribute("color", blue");

You can also add many attributes at once:

    Merx::cart()->setMultipleCustomAttribute([
        "coupon" => "ABC",
        "delivery" => "plane"
    ]);

And grab all attributes:

    Merx::cart()->allCustomAttributes(); // returns a Illuminate\Support\Collection

Finally, we can set attributes when adding the item:

    Merx::cart()->addItem([
        "article_id" => 1,
        "article_type" => App\Article::class,
        "name" => 'some tshirt',
        "price" => 1990,
        "quantity" => 1,
        "attributes" => [
            "color" => "blue"
        ]
    ]);

And same is true with a MerxItemMapper class: simply return an "attributes" array.

### A word on item equality

Consider this code:

    $domainArticle = App\Article::find(123);
    Merx::cart()->addItem($domainArticle, 1);
    Merx::cart()->addItem($domainArticle, 1);

Conveniently, Merx will add up those 2 articles in one item,
with a quantity of 2, avoiding the pain of manually check for
previous existence.

Now consider this code:

    $item = [
        "article_id" => 1,
        "article_type" => App\Article::class,
        "name" => 'some tshirt',
        "price" => 1990
    ];

    Merx::cart()->addItem(array_merge($item, [
        "attributes" => [
            "color" => "blue"
        ]
    ]));

    Merx::cart()->addItem(array_merge($item, [
        "attributes" => [
            "color" => "red"
        ]
    ]));

In this case, Merx **will not** merge those articles in one item,
because of the color attribute which differs.

In the highly hypothetical case where you would like to merge
articles anyway, without considering the attribute difference,
there is a way: Merx will look for a public static property of your
domain article class named `$merxCartItemAttributesExceptions`,
which must return an array with the names of the custom attributes to
ignore when comparing articles. So with this:

    [App\Article.php]
    class Article {
        public static $merxCartItemAttributesExceptions = [
            "color"
        ];

        [...]
    }

... and the previous code adding items of different colors, the red
one will disappear and quantity of the blue one will increase.

## Merx code license

[WTFPL](https://en.wikipedia.org/wiki/WTFPL)
