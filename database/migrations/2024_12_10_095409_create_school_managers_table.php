<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSchoolManagersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('school_managers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('uid')->foreign()->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->enum('manager_type', ['division', 'district', 'upazila', 'union', 'others'])->nullable();
            $table->text('profile_image')->nullable();
            $table->integer('assigned_division_id')->nullable();
            $table->integer('assigned_district_id')->nullable();
            $table->integer('assigned_upazila_id')->nullable();
            $table->integer('assigned_union_id')->nullable();
            $table->string('mikrotik_ip', 100);
            $table->string('mikrotik_username', 100);
            $table->string('mikrotik_password', 100);
            $table->enum('status', ['active', 'inactive', 'pending', 'suspended'])->nullable();
            $table->unsignedBigInteger('created_by')->foreign()->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('updated_by')->foreign()->references('id')->on('users')->onDelete('cascade');
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
        Schema::dropIfExists('school_managers');
    }
}
