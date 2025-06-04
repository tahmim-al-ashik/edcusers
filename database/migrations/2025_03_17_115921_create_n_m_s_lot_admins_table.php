<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNMSLotAdminsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('n_m_s_lot_admins', function (Blueprint $table) {
            $table->id();
            $table->integer('uid')->nullable();
            $table->string('name', 50)->nullable();
            $table->string('mobile_number', 15)->nullable();
            $table->string('whatsapp_number', 15)->nullable();
            $table->string('email', 50)->nullable();
            $table->string('lot_username', 100)->nullable();
            $table->string('lot_isp_name', 50)->nullable();
            $table->string('proprietor_name', 50)->nullable();
            $table->string('proprietor_mobile', 15)->nullable();
            $table->string('proprietor_email', 50)->nullable();
            $table->string('bank_name', 50)->nullable();
            $table->string('bank_account_name', 50)->nullable();
            $table->string('bank_account_number', 50)->nullable();
            $table->string('bank_branch_address', 256)->nullable();
            $table->decimal('installation_cost', 8, 2)->nullable();
            $table->integer('package_id')->nullable();
            $table->string('division_id', 50)->nullable();
            $table->string('district_id', 50)->nullable();
            $table->string('upazila_id', 50)->nullable();
            $table->string('union_id', 50)->nullable();
            $table->string('village_id', 50)->nullable();
            $table->string('latitude', 50)->nullable();
            $table->string('longitude', 50)->nullable();
            $table->tinyText('address_direction')->nullable();
            $table->string('status', 50)->nullable();
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
        Schema::dropIfExists('n_m_s_lot_admins');
    }
}
