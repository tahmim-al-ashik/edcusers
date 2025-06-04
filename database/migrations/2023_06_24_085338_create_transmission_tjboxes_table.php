<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransmissionTjboxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transmission_tjboxes', function (Blueprint $table) {
            $table->id();
            $table->integer('support_center_id');
            $table->text('type_of_fiber');
            $table->text('fiber_in_id')->nullable();
            $table->text('fiber_out_id')->nullable();
            $table->float('fiber_in_meter')->nullable();
            $table->float('fiber_out_meter')->nullable();
            $table->text('joining_core_color')->nullable();
            $table->text('splitter_ratio')->nullable();
            $table->float('latitude', 8, 6)->nullable();
            $table->float('longitude', 8, 6)->nullable();
            $table->string('status')->default('active');
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
        Schema::dropIfExists('transmission_tjboxes');
    }
}
