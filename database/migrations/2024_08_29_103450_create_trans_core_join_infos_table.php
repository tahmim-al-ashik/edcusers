<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransCoreJoinInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trans_core_join_infos', function (Blueprint $table) {
            $table->id();
            $table->integer('trans_id')->nullable();
            $table->string('module_type', 50)->nullable();

            $table->unsignedBigInteger('in_fiber_id')->nullable();
            $table->foreign('in_fiber_id')->references('id')->on('trans_cable_details')->onDelete('cascade');

            $table->unsignedBigInteger('out_fiber_id')->nullable();
            $table->foreign('out_fiber_id')->references('id')->on('trans_cable_details')->onDelete('cascade');

            $table->string('joining_core_color', 50)->nullable();
            $table->string('db_signal', 50)->nullable();
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
        Schema::dropIfExists('trans_core_join_infos');
    }
}
