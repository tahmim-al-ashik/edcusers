<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransmissionCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transmission_companies', function (Blueprint $table) {
            $table->id();
            $table->text('name');
            $table->text('contact_person_name_first')->nullable();
            $table->text('contact_person_number_first')->nullable();
            $table->text('contact_person_designation_first')->nullable();
            $table->text('contact_person_name_sec')->nullable();
            $table->text('contact_person_number_sec')->nullable();
            $table->text('contact_person_designation_sec')->nullable();
            $table->text('company_type')->nullable();
            $table->text('vendor_name')->nullable();
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
        Schema::dropIfExists('transmission_companies');
    }
}
