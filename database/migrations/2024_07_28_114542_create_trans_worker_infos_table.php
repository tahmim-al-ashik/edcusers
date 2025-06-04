<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransWorkerInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trans_worker_infos', function (Blueprint $table) {
            $table->id();
            $table->integer('trans_id')->nullable();
            $table->string('module_type', 50)->nullable();
            $table->string('added_by_name', 50)->nullable();
            $table->string('mobile_number', 50)->nullable();
            $table->string('work_type', 50)->nullable();
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
        Schema::dropIfExists('trans_worker_infos');
    }
}
