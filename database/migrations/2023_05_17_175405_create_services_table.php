<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->text('bn_title');
            $table->text('en_title');
            $table->text('service_group');
            $table->float('price', 10, 2);
            $table->float('sales_point_commission', 10, 2)->default(0);
            $table->float('sales_agent_commission', 10, 2)->default(0);
            $table->string('commission_type')->default('percentage');
            $table->integer('is_active')->default(0);
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
        Schema::dropIfExists('services');
    }
}
