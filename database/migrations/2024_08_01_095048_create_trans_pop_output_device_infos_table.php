<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransPopOutputDeviceInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trans_pop_output_device_infos', function (Blueprint $table) {
            $table->id();
            $table->integer('trans_id')->nullable();
            $table->string('module_type', 50)->nullable();

            $table->string('output_device_type', 50)->nullable();
            $table->string('output_device_port_type', 50)->nullable();
            $table->string('output_device_port_number', 50)->nullable();
            $table->string('output_device_brand_name', 50)->nullable();
            $table->string('output_device_connection_capacity', 50)->nullable();
            $table->string('output_device_serial_no', 50)->nullable();
            $table->string('output_device_id', 50)->nullable();
            $table->string('output_device_power_consumption', 50)->nullable();
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
        Schema::dropIfExists('trans_pop_output_device_infos');
    }
}
