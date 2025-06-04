<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesAgentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_agents', function (Blueprint $table) {
            $table->id();
            $table->integer('uid');
            $table->text('zone_id')->nullable();
            $table->float('monthly_commission_rate')->default(0);
            $table->text('nid')->nullable();
            $table->dateTime('birth_date');
            $table->string('status')->default('pending');
            $table->string('photo_source')->nullable();
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
        Schema::dropIfExists('sales_agents');
    }
}
