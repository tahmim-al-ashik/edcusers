<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransmissionCustomersProblemChecksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transmission_customers_problem_checks', function (Blueprint $table) {
            $table->id();
            $table->text('customer_name');
            $table->text('mobile_number')->nullable();
            $table->text('email')->nullable();
            $table->text('contact_name')->nullable();
            $table->text('contact_number')->nullable();
            $table->text('contact_email')->nullable();
            $table->text('contact_designation')->nullable();
            $table->text('organization')->nullable();
            $table->float('latitude', 8, 6)->nullable();
            $table->float('longitude', 8, 6)->nullable();
            $table->text('package_name')->nullable();
            $table->integer('division_id')->nullable();
            $table->integer('district_id')->nullable();
            $table->integer('upazila_id')->nullable();
            $table->integer('union_id')->nullable();
            $table->integer('village_id')->nullable();
            $table->text('address')->nullable();
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
        Schema::dropIfExists('transmission_customers_problem_checks');
    }
}
