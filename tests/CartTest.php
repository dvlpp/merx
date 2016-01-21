<?php

use Dvlpp\Merx\Exceptions\CartClosedException;
use Dvlpp\Merx\Models\Cart;
use Dvlpp\Merx\Models\CartItem;
use Dvlpp\Merx\Models\CartItemMapper;

class CartTest extends TestCase
{

    /** @test */
    public function we_can_add_an_item()
    {
        $itemAttributes = $this->itemAttributes();

        $cart = $this->newCart();

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
    public function we_can_find_an_exisiting_item_by_its_ref()
    {
        $itemAttributes = $this->itemAttributes();
        $itemRef = $itemAttributes["ref"];

        $cart = $this->newCart();
        $cart->addItem($itemAttributes);

        $item = $cart->item($itemRef);

        $this->assertInstanceOf(CartItem::class, $item);
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
        $cart->removeItem($item->ref);

        $this->assertCount(1, $cart->items);

        $this->dontSeeInDatabase('merx_cart_items', [
            "id" => $item->id
        ]);

        // Remove second item directly, not by ref
        $cart->removeItem($item2);

        $this->assertCount(0, $cart->items);

        $this->dontSeeInDatabase('merx_cart_items', [
            "id" => $item->id
        ]);
    }

    /** @test */
    public function we_can_add_a_mapped_product_in_the_cart()
    {
        $product = new stdClass();
        $product->ref = "123";
        $product->label = "T-shirt";
        $product->price = 22.50;
        $product->description = "A nice blue t-shirt";

        $this->app['config']->set('merx.item_mapper', ProductToCartItemMapper::class);

        $cart = $this->newCart();
        $cart->addItem($product, 1);

        $this->assertCount(1, $cart->items);

        $this->seeInDatabase('merx_cart_items', [
            "ref" => "123",
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
        $product->ref = "123";
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
        $cart->updateItemQuantity($item->ref, 2);

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

        $cart->close();

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
            "ref" => $object->ref
        ];
    }
}