<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransCableDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trans_cable_details', function (Blueprint $table) {
            $table->id();
            $table->integer('trans_id')->nullable();
            $table->string('module_type', 50)->nullable();
            $table->string('cable_type', 50)->nullable();
            $table->string('fiber_code', 50)->nullable();
            $table->string('fiber_core', 50)->nullable();
            $table->string('core_capacity', 50)->nullable();
            $table->string('start_fiber_meter', 50)->nullable();
            $table->string('end_fiber_meter', 50)->nullable();
            $table->string('fiber_length', 50)->nullable();
            $table->string('joining_core_color', 50)->nullable();
            $table->string('db_signal', 50)->nullable();
            $table->string('connected_port_number', 50)->nullable();
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
        Schema::dropIfExists('trans_cable_details');
    }
}
