<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransPopDeviceInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trans_pop_device_infos', function (Blueprint $table) {
            $table->id();
            $table->integer('trans_id')->nullable();
            $table->string('module_type', 50)->nullable();
            $table->string('sfp_brand_name', 50)->nullable();
            $table->string('sfp_type', 50)->nullable();
            $table->string('sfp_capacity', 50)->nullable();
            $table->string('input_device_port_type', 50)->nullable();
            $table->string('port_capacity', 50)->nullable();
            $table->string('incoming_fiber_connected_port_number', 50)->nullable();
            $table->string('mk_brand_name', 50)->nullable();
            $table->string('mk_capacity', 50)->nullable();
            $table->string('mk_port_number', 50)->nullable();
            $table->string('mk_serial_no', 50)->nullable();
            $table->string('mk_device_id', 50)->nullable();
            $table->string('mk_power_consumption', 50)->nullable();
            $table->string('mk_mac_address', 50)->nullable();
            $table->string('rak_brand_name', 50)->nullable();
            $table->string('rak_capacity', 50)->nullable();
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
        Schema::dropIfExists('trans_pop_device_infos');
    }
}
