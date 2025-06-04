<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransmissionPopProblemChecksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transmission_pop_problem_checks', function (Blueprint $table) {
            $table->id();
            $table->integer('tc_id');
            $table->text('category');
            $table->text('nttn_pop_code')->nullable();
            $table->text('pop_id')->nullable();
            $table->text('infra_type')->nullable();
            $table->text('indoor_outdoor')->nullable();
            $table->text('pop_type')->nullable();
            $table->float('latitude', 8, 6);
            $table->float('longitude', 8, 6);
            $table->integer('division_id')->nullable();
            $table->integer('district_id')->nullable();
            $table->integer('upazila_id')->nullable();
            $table->integer('union_id')->nullable();
            $table->integer('village_id')->nullable();
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
        Schema::dropIfExists('transmission_pop_problem_checks');
    }
}
