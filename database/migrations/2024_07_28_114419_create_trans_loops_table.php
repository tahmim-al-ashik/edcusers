<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransLoopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trans_loops', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('pop_id')->nullable();
            $table->foreign('pop_id')->references('id')->on('trans_pops')->onDelete('cascade');

            $table->unsignedBigInteger('tj_box_id')->nullable();
            $table->foreign('tj_box_id')->references('id')->on('trans_tj_boxes')->onDelete('cascade');

            $table->string('olt_port', 50)->nullable();

            $table->string('loop_code', 50)->nullable();
            $table->string('loop_type', 50)->nullable();

            $table->string('latitude', 50)->nullable();
            $table->string('longitude', 50)->nullable();
            $table->text('address_direction')->nullable();

            $table->unsignedBigInteger('added_by_uid')->nullable();
            $table->foreign('added_by_uid')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('updated_by_uid')->nullable();
            $table->foreign('updated_by_uid')->references('id')->on('users')->onDelete('cascade');

            $table->text('comments')->nullable();
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
        Schema::dropIfExists('trans_loops');
    }
}
