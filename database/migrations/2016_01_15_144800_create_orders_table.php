<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('merx_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('cart_id')->unsigned();
            $table->integer('client_id')->unsigned();
            $table->string("ref")->index();
            $table->string("state");
            $table->text("custom_attributes")->nullable();
            $table->nullableTimestamps();
        });

        Schema::table('merx_orders', function (Blueprint $table) {
            $table->foreign('cart_id')
                ->references('id')
                ->on('merx_carts')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('merx_orders');
    }
}
