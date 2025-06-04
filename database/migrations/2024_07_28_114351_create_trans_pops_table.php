<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransPopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trans_pops', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')->references('id')->on('trans_companies')->onDelete('cascade');

            $table->string('pop_code', 50);
            $table->string('nttn_pop_code', 50);
            $table->string('pop_sl_no', 50);
            $table->string('pop_type', 50);
            $table->string('pop_main_type', 50);

            $table->unsignedBigInteger('parent_pop_id')->nullable();
            $table->foreign('parent_pop_id')->references('id')->on('trans_pops')->onDelete('cascade');

            $table->unsignedBigInteger('nttn_pop_id')->nullable();
            $table->foreign('nttn_pop_id')->references('id')->on('trans_pops')->onDelete('cascade');

            $table->unsignedBigInteger('backup_nttn_pop_id')->nullable();
            $table->foreign('backup_nttn_pop_id')->references('id')->on('trans_pops')->onDelete('cascade');

            $table->string('scr_id', 50)->nullable();
            $table->string('db_signal', 50)->nullable();

            $table->string('division_id', 50)->nullable();
            $table->string('district_id', 50)->nullable();
            $table->string('upazila_id', 50)->nullable();
            $table->string('union_id', 50)->nullable();
            $table->string('village_name', 50)->nullable();
            $table->text('address_direction')->nullable();
            $table->string('latitude', 50)->nullable();
            $table->string('longitude', 50)->nullable();

            $table->unsignedBigInteger('added_by_uid');
            $table->foreign('added_by_uid')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('updated_by_uid');
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
        Schema::dropIfExists('trans_pops');
    }
}
