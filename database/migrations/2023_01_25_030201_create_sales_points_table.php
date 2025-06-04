<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesPointsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_points', function (Blueprint $table) {
            $table->id();
            $table->text('zone_id')->nullable();
            $table->integer('uid');
            $table->text('store_name')->nullable();
            $table->float('monthly_commission_rate')->default(0);
            $table->integer('division_id');
            $table->integer('district_id');
            $table->integer('upazila_id');
            $table->integer('union_id');
            $table->integer('village_id');
            $table->string('address');
            $table->float('latitude', 8, 6);
            $table->float('longitude', 8, 6);
            $table->text('trade_licence')->nullable();
            $table->string('status')->default('pending');
            $table->string('logo_source')->nullable();
            $table->longText('data_object');
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
        Schema::dropIfExists('sales_points');
    }
}
