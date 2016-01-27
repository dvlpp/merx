<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCartItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('merx_cart_items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('cart_id')->unsigned();
            $table->string("name");
            $table->string("details")->nullable();
            $table->integer("price")->unsigned();
            $table->smallInteger("quantity")->unsigned();
            $table->json("custom_attributes")->nullable();

            // Morph link to the real article
            $table->string("article_id");
            $table->string("article_type");
            $table->index(["article_id", "article_type"]);

            $table->foreign('cart_id')
                ->references('id')
                ->on('merx_carts')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('merx_cart_items');
    }
}
