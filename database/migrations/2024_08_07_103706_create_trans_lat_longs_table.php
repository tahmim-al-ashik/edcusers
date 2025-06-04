<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransLatLongsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trans_lat_longs', function (Blueprint $table) {
            $table->id();
            $table->integer('trans_id')->nullable();
            $table->string('module_type',50)->nullable();
            $table->string('division_id', 50)->nullable();
            $table->string('district_id', 50)->nullable();
            $table->string('upazila_id', 50)->nullable();
            $table->string('union_id', 50)->nullable();
            $table->string('latitude', 50)->nullable();
            $table->string('longitude', 50)->nullable();
            $table->string('status', 50)->nullable();
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
        Schema::dropIfExists('trans_lat_longs');
    }
}
