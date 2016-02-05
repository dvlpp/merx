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

        // We explicitly load the relation to ensure it is updated
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
    public function we_can_get_an_exisiting_item_by_its_id()
    {
        $itemAttributes = $this->itemAttributes();

        $cart = $this->newCart();
        $item = $cart->addItem($itemAttributes);

        $item2 = $cart->findItem($item->id);

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

        // Remove first item by id
        $cart->removeItem($item2->id);

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
        $product = $this->createMappedDomainObject();

        $cart = $this->newCart();
        $cart->addItem($product, 1);

        $this->assertCount(1, $cart->items);

        $this->seeInDatabase('merx_cart_items', [
            "article_id" => $product->id,
            "name" => $product->label,
            "price" => $product->price * 100,
            "details" => $product->description,
            "cart_id" => $cart->id
        ]);
    }

    /** @test */
    public function we_cant_add_an_item_with_invalid_attributes_in_the_cart()
    {
        $attributes = $this->itemAttributes();
        unset($attributes["name"]);

        $cart = $this->newCart();

        $this->setExpectedException(InvalidCartItemException::class);

        $cart->addItem($attributes);
    }

    /** @test */
    public function we_can_update_an_item_quantity()
    {
        $item = new CartItem($this->itemAttributes());
        $item->quantity = 1;

        $cart = $this->newCart();
        $cart->addItem($item);

        // Update qty passing ref
        $cart->updateItemQuantity($item, 2);

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
    public function when_adding_2_items_with_same_article_and_no_custom_attribute_we_add_up_quantities()
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
    public function when_adding_2_items_with_same_article_and_same_custom_attributes_we_add_up_quantities()
    {
        $cart = $this->newCart();

        $itemAttributes = $this->itemAttributes();
        $itemAttributes["quantity"] = 1;
        $itemAttributes["attributes"] = [
            "custom" => "value"
        ];

        $cart->addItem($itemAttributes);
        $cart->addItem($itemAttributes);

        $this->assertEquals(2, $cart->itemsCount());
        $this->assertCount(1, $cart->items);
    }

    /** @test */
    public function when_adding_2_items_with_same_article_and_different_custom_attributes_we_dont_add_up_quantities()
    {
        $cart = $this->newCart();

        $itemAttributes = $this->itemAttributes();
        $itemAttributes["quantity"] = 1;
        $itemAttributes["attributes"] = [
            "custom" => "value"
        ];
        $cart->addItem($itemAttributes);

        $itemAttributes["attributes"] = [
            "custom" => "aDifferentValue"
        ];
        $cart->addItem($itemAttributes);

        $this->assertEquals(2, $cart->itemsCount());
        $this->assertCount(2, $cart->items);
    }

    /** @test */
    public function when_adding_2_mapped_objects_with_same_article_and_different_custom_attributes_we_dont_add_up_quantities(
    )
    {
        $cart = $this->newCart();

        $product = $this->createMappedDomainObject(1);
        $product->attributes = [
            "custom" => "value"
        ];

        $product2 = $this->createMappedDomainObject(1);
        $product2->attributes = [
            "custom" => "value2"
        ];

        $cart->addItem($product, 1);
        $cart->addItem($product2, 1);

        $this->assertEquals(2, $cart->itemsCount());
        $this->assertCount(2, $cart->items);
    }

    /** @test */
    public function when_adding_2_mapped_objects_with_same_article_and_different_custom_attributes_with_except_rules_we_dont_add_up_quantities(
    )
    {
        $cart = $this->newCart();

        $product = $this->createMappedDomainObject(1);
        // This attribute is an exception defined in CartItemMapper.customAttributesNotPartOfId() method
        // and must be ignored when comparing 2 items for equality
        $product->attributes = [
            "custom_out_of_id" => "value"
        ];

        $product2 = $this->createMappedDomainObject(1);
        $product2->attributes = [
            "custom_out_of_id" => "aDifferentValue"
        ];

        $cart->addItem($product, 1);
        $cart->addItem($product2, 1);

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

    /** @test */
    public function we_can_add_an_item_with_custom_attributes()
    {
        $cart = Cart::create();

        $attributes = $this->itemAttributes();
        $attributes["attributes"]["custom"] = "value";

        $item = $cart->addItem($attributes);

        $this->assertEquals("value", $item->customAttribute("custom"));

        $this->seeInDatabase('merx_cart_items', [
            "id" => $item->id,
            "custom_attributes" => json_encode([
                "custom" => "value"
            ])
        ]);
    }

    /** @test */
    public function we_can_add_a_mapped_product_with_custom_attributes()
    {
        $product = $this->createMappedDomainObject();
        $product->attributes = [
            "custom" => "value"
        ];

        $this->app['config']->set('merx.item_mapper', ProductToCartItemMapper::class);

        $cart = $this->newCart();
        $cart->addItem($product, 1);

        $this->seeInDatabase('merx_cart_items', [
            "article_id" => $product->id,
            "name" => $product->label,
            "price" => $product->price * 100,
            "details" => $product->description,
            "cart_id" => $cart->id,
            "custom_attributes" => json_encode([
                "custom" => "value"
            ])
        ]);
    }

    private function newCart()
    {
        return Cart::create();
    }

    /**
     * @return stdClass
     */
    private function createMappedDomainObject($id = null)
    {
        $this->app['config']->set('merx.item_mapper', ProductToCartItemMapper::class);

        $id = $id ?: rand(1, 1000000);

        if (!TestArticle::find($id)) {
            TestArticle::create(["id" => $id]);
        }

        $product = new stdClass();
        $product->id = $id;
        $product->label = "T-shirt";
        $product->price = 22.50;
        $product->description = "A nice blue t-shirt";

        return $product;
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
        $tab = [
            "name" => $object->label,
            "price" => $object->price * 100,
            "details" => $object->description,
            "article_id" => $object->id,
            "article_type" => TestArticle::class,
        ];

        if (isset($object->attributes)) {
            $tab["attributes"] = $object->attributes;
        }

        return $tab;
    }
}