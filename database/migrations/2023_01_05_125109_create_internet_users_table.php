<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInternetUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('internet_users', function (Blueprint $table) {
            $table->id();
            $table->integer('uid');
            $table->text('zone_id')->nullable();
            $table->integer('package_id');
            $table->text('package_type');
            $table->DateTime('package_expire_date')->nullable();
            $table->text('previous_conn_type')->nullable();
            $table->text('provider_names')->nullable();
            $table->text('latitude');
            $table->text('longitude');
            $table->text('password')->nullable();
            $table->text('password_broadband')->nullable();
            $table->text('user_type')->nullable();
            $table->mediumText('billing_address')->nullable();
            $table->integer('serial_number')->nullable();
            $table->text('broadband_pop_id')->nullable();
            $table->text('connection_media')->nullable();
            $table->float('installation_charge')->nullable();
            $table->string('connection_status')->default('pending');
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
        Schema::dropIfExists('internet_users');
    }
}
