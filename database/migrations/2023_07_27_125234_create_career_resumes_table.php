<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCareerResumesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('career_resumes', function (Blueprint $table) {
            $table->id();
            $table->integer('career_id');
            $table->text('full_name_bn');
            $table->text('full_name_en');
            $table->text('mobile_number');
            $table->text('whatsapp_number')->nullable();
            $table->text('email')->nullable();
            $table->text('nid_number')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('nationality')->nullable();
            $table->integer('division_id');
            $table->integer('district_id');
            $table->integer('upazila_id');
            $table->integer('union_id');
            $table->integer('village_id');
            $table->integer('address_details')->nullable();
            $table->text('working_time')->nullable();
            $table->text('expected_salary')->nullable();
            $table->text('latitude')->nullable();
            $table->text('longitude')->nullable();
            $table->mediumText('educations');
            $table->mediumText('certifications');
            $table->mediumText('experiences');
            $table->mediumText('languages');
            $table->mediumText('others_activity')->nullable();
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
        Schema::dropIfExists('career_resumes');
    }
}
