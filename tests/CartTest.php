<?php

use Dvlpp\Merx\Exceptions\CartClosedException;
use Dvlpp\Merx\Exceptions\CartItemNotFoundException;
use Dvlpp\Merx\Exceptions\InvalidCartItemException;
use Dvlpp\Merx\Models\Cart;
use Dvlpp\Merx\Models\CartItem;
use Dvlpp\Merx\Models\CartItemMapper;
use Dvlpp\Merx\Models\Order;

class CartTest extends TestCase
{

    /** @test */
    public function we_can_add_an_item()
    {
        $itemAttributes = $this->itemAttributes();

        $cart = $this->newCart();

        $this->assertCount(0, $cart->items);

        $item = $cart->addItem($itemAttributes);

        $this->assertCount(1, $cart->items);

        $this->seeInDatabase('merx_cart_items',
            array_merge(
                ["id" => $item->id],
                $itemAttributes
            )
        );
    }

    /** @test */
    public function we_can_find_an_exisiting_item_by_its_article_ref()
    {
        $itemAttributes = $this->itemAttributes();
        $itemRef = $itemAttributes["article_id"];

        $cart = $this->newCart();
        $cart->addItem($itemAttributes);

        $item = $cart->findItem($itemRef);

        $this->assertInstanceOf(CartItem::class, $item);
    }

    /** @test */
    public function we_can_get_an_exisiting_item_by_its_id()
    {
        $itemAttributes = $this->itemAttributes();

        $cart = $this->newCart();
        $item = $cart->addItem($itemAttributes);

        $item2 = $cart->getItem($item->id);

        $this->assertInstanceOf(CartItem::class, $item2);
        $this->assertEquals($item->id, $item2->id);
    }

    /** @test */
    public function we_can_calculate_the_total_cart_value()
    {
        $item1 = new CartItem($this->itemAttributes());
        $item2 = new CartItem($this->itemAttributes());

        $item1->price = 1000;
        $item1->quantity = 2;
        $item2->price = 1200;
        $item2->quantity = 3;

        $cart = $this->newCart();
        $cart->items()->save($item1);
        $cart->items()->save($item2);

        $this->assertEquals(1000 * 2 + 1200 * 3, $cart->total());
    }

    /** @test */
    public function we_can_get_items_count()
    {
        $item1 = new CartItem($this->itemAttributes());
        $item2 = new CartItem($this->itemAttributes());

        $cart = $this->newCart();
        $cart->addItem($item1);
        $cart->addItem($item2);

        $this->assertEquals($item1->quantity + $item2->quantity, $cart->itemsCount());
    }

    /** @test */
    public function we_can_remove_an_item()
    {
        $item = new CartItem($this->itemAttributes());
        $item2 = new CartItem($this->itemAttributes());

        $cart = $this->newCart();
        $cart->items()->save($item);
        $cart->items()->save($item2);

        // Remove first item by ref
        $cart->removeItem($item2->article_id);

        $this->assertCount(1, $cart->items);

        $this->dontSeeInDatabase('merx_cart_items', [
            "id" => $item2->id
        ]);

        // Remove second item directly, not by ref
        $cart->removeItem($item);

        $this->assertCount(0, $cart->items);

        $this->dontSeeInDatabase('merx_cart_items', [
            "id" => $item->id
        ]);
    }

    /** @test */
    public function we_can_add_a_mapped_product_in_the_cart()
    {
        $product = new stdClass();
        $product->id = 123;
        $product->label = "T-shirt";
        $product->price = 22.50;
        $product->description = "A nice blue t-shirt";

        $this->app['config']->set('merx.item_mapper', ProductToCartItemMapper::class);

        $cart = $this->newCart();
        $cart->addItem($product, 1);

        $this->assertCount(1, $cart->items);

        $this->seeInDatabase('merx_cart_items', [
            "article_id" => "123",
            "name" => "T-shirt",
            "price" => 2250,
            "details" => "A nice blue t-shirt",
            "cart_id" => $cart->id
        ]);
    }

    /** @test */
    public function we_can_remove_a_mapped_product_from_the_cart()
    {
        $product = new stdClass();
        $product->id = "123";
        $product->label = "T-shirt";
        $product->price = 22.50;
        $product->description = "A nice blue t-shirt";

        $this->app['config']->set('merx.item_mapper', ProductToCartItemMapper::class);

        $cart = $this->newCart();
        $cartItem = $cart->addItem($product, 1);

        // Remove item passing the original object
        $cart->removeItem($product);

        $this->assertCount(0, $cart->items);

        $this->dontSeeInDatabase('merx_cart_items', [
            "id" => $cartItem->id
        ]);
    }

    /** @test */
    public function we_can_update_an_item_quantity()
    {
        $item = new CartItem($this->itemAttributes());
        $item->quantity = 1;

        $cart = $this->newCart();
        $cart->addItem($item);

        // Update qty passing ref
        $cart->updateItemQuantity($item->article_id, 2);

        $this->seeInDatabase('merx_cart_items', [
            "id" => $item->id,
            "quantity" => 2
        ]);

        // Update qty passing $item
        $cart->updateItemQuantity($item, 4);

        $this->seeInDatabase('merx_cart_items', [
            "id" => $item->id,
            "quantity" => 4
        ]);
    }

    /** @test */
    public function we_can_empty_the_cart()
    {
        $item1 = new CartItem($this->itemAttributes());
        $item2 = new CartItem($this->itemAttributes());

        $cart = $this->newCart();
        $cart->addItem($item1);
        $cart->addItem($item2);

        $cart->emptyCart();

        $this->assertCount(0, $cart->items);

        $this->dontSeeInDatabase('merx_cart_items', [
            "cart_id" => $cart->id
        ]);
    }

    /** @test */
    public function we_cant_add_an_item_on_a_closed_cart()
    {
        $cart = Cart::create();
        session()->put("merx_cart_id", $cart->id);

        $cart->addItem(new CartItem($this->itemAttributes()));

        $client = $this->loginClient();

        Order::create([
            "client_id" => $client->id,
            "ref" => "123"
        ])->update([
            "state" => "completed"
        ]);

        $cart = $cart->fresh();

        $this->setExpectedException(CartClosedException::class);

        $cart->addItem(new CartItem($this->itemAttributes()));
    }

    /** @test */
    public function the_minimum_quantity_when_adding_an_item_is_one()
    {
        $itemAttributes = $this->itemAttributes();
        unset($itemAttributes["quantity"]);

        $cart = $this->newCart();

        $item = $cart->addItem($itemAttributes);

        $this->assertEquals(1, $cart->itemsCount());

        $this->seeInDatabase('merx_cart_items', [
            "id" => $item->id,
            "quantity" => 1
        ]);
    }

    /** @test */
    public function when_adding_an_item_which_already_exists_in_cart_we_add_up_quantities()
    {
        $cart = $this->newCart();

        $itemAttributes = $this->itemAttributes();
        $itemAttributes["quantity"] = 1;

        $cart->addItem($itemAttributes);
        $cart->addItem($itemAttributes);

        $this->assertEquals(2, $cart->itemsCount());
        $this->assertCount(1, $cart->items);
    }

    /** @test */
    public function we_cant_add_an_invalid_item()
    {
        $cart = $this->newCart();

        $this->setExpectedException(InvalidCartItemException::class);

        $cart->addItem("test");
    }

    /** @test */
    public function we_cant_update_a_non_existing_item()
    {
        $cart = $this->newCart();

        $this->setExpectedException(CartItemNotFoundException::class);

        $cart->updateItemQuantity("123", 2);
    }

    /** @test */
    public function we_get_the_related_product_from_a_cart_item()
    {
        $item = new CartItem($this->itemAttributes());
        $item["article_id"] = 1;

        $cart = $this->newCart();
        $cart->addItem($item);

        TestArticle::create(["id" => 1]);

        $article = $item->article;

        $this->assertInstanceOf(TestArticle::class, $article);
        $this->assertEquals(1, $article->id);
    }

    /** @test */
    public function we_can_add_custom_attribute_to_a_cart_item()
    {
        $cart = Cart::create();

        $item = new CartItem($this->itemAttributes());
        $cart->addItem($item);

        $item->setCustomAttribute("custom", "value");

        $this->assertEquals("value", $item->customAttribute("custom"));

        $this->seeInDatabase('merx_cart_items', [
            "id" => $item->id,
            "custom_attributes" => json_encode([
                "custom" => "value"
            ])
        ]);
    }

    private function newCart()
    {
        return Cart::create();
    }
}

class ProductToCartItemMapper implements CartItemMapper
{
    /**
     * @param $object
     * @return array
     */
    public function mapCartItemAttributes($object)
    {
        return [
            "name" => $object->label,
            "price" => $object->price * 100,
            "details" => $object->description,
            "id" => $object->id,
            "type" => TestArticle::class
        ];
    }
}