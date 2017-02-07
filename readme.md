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

- <code>name</code>: the item name
- <code>price</code>: the item price, in cents
- <code>details</code>: item details (optional)
- <code>article_id</code>: the model object id
- <code>article_type</code>: the model object full class name

And <code>quantity</code>: the item quantity, which can be
missing if quantity is set at the <code>addItem()</code> state.

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

**[Laravel 5.2 and 5.3 only]** Alternatively, you can run the <code>php artisan merx:migrate</code>
to migrate the db without adding the migration classes, as part of your
deployment process. This command **will not** erase existing tables,
except with the <code>--refresh</code> option.

### Adapt your User class (optional)

Finally, Merx use the Laravel auth system, with the standard User class.
If you need to separate Merx Users from other, you can define a
<code>isMerxUser()</code> function in your User model. If not present,
Merx will assume it's always true.

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

If the article was already in the cart (same article_id), quantity would add up.

### Remove an item from the cart

Well...

    Merx::cart()->removeItem($item);

$item can either be:

- an instance of <code>CartItem</code>
- the <code>CartItem</code> id

To clear the cart, you can call:

    Merx::cart()->emptyCart();

### And the rest of it

    $tshirt = App\Article::find(1);
    $item = Merx::cart()->addItem($tshirt, 1);
    Merx::cart()->updateItemQuantity($shirt, 2);
    Merx::cart()->itemsCount(); // 2
    Merx::cart()->items; // Collection
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
- or there's no current authenticated client

On the second case: Merx is assuming that you use the standard Laravel auth system.
The idea is that if there's no current auth user, you can
redirect to a login page.

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
domain article class named <code>$merxCartItemAttributesExceptions</code>,
which must return an array wit the names of tehe custom attributes to
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
