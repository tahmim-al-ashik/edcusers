<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransTjBoxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trans_tj_boxes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pop_id');
            $table->foreign('pop_id')->references('id')->on('trans_pops')->onDelete('cascade');

            $table->string('tj_box_code', 50)->nullable();
            $table->string('tj_box_type', 50)->nullable();
            $table->string('olt_port', 50)->nullable();

            $table->unsignedBigInteger('parent_tj_box_id')->nullable();
            $table->foreign('parent_tj_box_id')->references('id')->on('trans_tj_boxes')->onDelete('cascade');

            $table->string('customer_name', 50)->nullable();
            $table->string('customer_mobile', 50)->nullable();

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
        Schema::dropIfExists('trans_tj_boxes');
    }
}
