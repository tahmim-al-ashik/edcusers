<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->integer('store_id')->nullable();
            $table->text('title');
            $table->integer('category_id');
            $table->float('price');
            $table->float('sales_point_commission')->default(0);
            $table->float('sales_agent_commission')->default(0);
            $table->string('commission_type')->default('percentage');
            $table->integer('is_featured')->default(0);
            $table->integer('is_active')->default(1);
            $table->text('image_source')->nullable();
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
        Schema::dropIfExists('products');
    }
}
