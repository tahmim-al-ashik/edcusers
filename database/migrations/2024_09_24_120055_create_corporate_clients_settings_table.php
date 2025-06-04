<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCorporateClientsSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('corporate_clients_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('client_uid')->nullable();
            $table->string('logo')->nullable();
            $table->string('signature')->nullable();
            $table->string('billing_cycle')->nullable();
            $table->integer('manual_disable_day')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('bkash_username')->nullable();
            $table->string('bkash_password')->nullable();
            $table->string('bkash_app_key')->nullable();
            $table->string('bkash_app_secret_key')->nullable();
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
        Schema::dropIfExists('corporate_clients_settings');
    }
}
