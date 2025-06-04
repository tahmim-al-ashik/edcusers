<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductPurchaseRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->integer('uid');
            $table->integer('product_id');
            $table->text('company_name');
            $table->text('isp_name')->nullable();
            $table->integer('broadband_users')->nullable();
            $table->integer('wifi_users')->nullable();
            $table->text('business_type')->nullable();
            $table->integer('internet_bandwidth')->nullable();
            $table->integer('youtube')->nullable();
            $table->integer('facebook')->nullable();
            $table->integer('bdix')->nullable();
            $table->text('nttn')->nullable();
            $table->integer('number_of_pop')->default(0);
            $table->text('ref_name')->nullable();
            $table->text('ref_mobile_number')->nullable();
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
        Schema::dropIfExists('product_purchase_requests');
    }
}
