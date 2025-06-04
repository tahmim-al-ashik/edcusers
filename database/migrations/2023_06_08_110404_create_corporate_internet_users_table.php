<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCorporateInternetUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('corporate_internet_users', function (Blueprint $table) {
            $table->id();
            $table->integer('client_id');
            $table->text('name');
            $table->text('username');
            $table->text('password');
            $table->integer('package_id');
            $table->text('user_type');
            $table->dateTime('activation_date');
            $table->dateTime('expire_date')->nullable();
            $table->integer('is_active')->default(1);
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
        Schema::dropIfExists('corporate_internet_users');
    }
}
