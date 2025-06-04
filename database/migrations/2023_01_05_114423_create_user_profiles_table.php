<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->integer('uid');
            $table->text('full_name');
            $table->float('wallet_amount')->default(0.0);
            $table->text('mobile_number')->nullable();
            $table->text('whatsapp_number')->nullable();
            $table->text('email')->nullable();
            $table->text('profession')->nullable();
            $table->text('nid')->nullable();
            $table->text('gender')->nullable();
            $table->integer('division_id')->nullable();
            $table->integer('district_id')->nullable();
            $table->integer('upazila_id')->nullable();
            $table->integer('union_id')->nullable();
            $table->integer('village_id')->nullable();
            $table->text('house_no')->nullable();
            $table->text('ward_no')->nullable();
            $table->text('road_no')->nullable();
            $table->text('block_no')->nullable();
            $table->string('address')->nullable();
            $table->text('latitude')->nullable();
            $table->text('longitude')->nullable();
            $table->text('address_direction')->nullable();
            $table->mediumText('device_info')->nullable();
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
        Schema::dropIfExists('user_profiles');
    }
}
