<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNMSCategoryBasedAdminsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('n_m_s_category_based_admins', function (Blueprint $table) {
            $table->id();
            $table->integer('uid')->nullable();
            $table->integer('lot_id')->nullable();
            $table->string('category_type', 256)->nullable();
            $table->string('division_id', 50)->nullable();
            $table->string('district_id', 50)->nullable();
            $table->string('upazila_id', 50)->nullable();
            $table->string('union_id', 50)->nullable();
            $table->string('village_id', 50)->nullable();
            $table->string('latitude', 50)->nullable();
            $table->string('longitude', 50)->nullable();
            $table->string('address_direction', 256)->nullable();
            $table->enum('status', ['active', 'inactive', 'pending', 'suspended'])->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
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
        Schema::dropIfExists('n_m_s_category_based_admins');
    }
}
