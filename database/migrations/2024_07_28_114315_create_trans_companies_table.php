<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trans_companies', function (Blueprint $table) {
            $table->id();
            $table->string('company_name', 100);
            $table->string('company_type', 50);
            $table->string('contact_person_name_pri', 100)->nullable();
            $table->string('contact_person_number_pri', 50)->nullable();
            $table->string('contact_person_email_pri', 50)->nullable();
            $table->string('contact_person_designation_pri', 50)->nullable();
            $table->string('contact_person_name_sec', 100)->nullable();
            $table->string('contact_person_number_sec', 50)->nullable();
            $table->string('contact_person_email_sec', 50)->nullable();
            $table->string('contact_person_designation_sec', 50)->nullable();
            $table->string('vendor_name', 50)->nullable();

            $table->unsignedBigInteger('added_by_uid')->nullable();
            $table->foreign('added_by_uid')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('updated_by_uid')->nullable();
            $table->foreign('updated_by_uid')->references('id')->on('users')->onDelete('cascade');

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
        Schema::dropIfExists('trans_companies');
    }
}
