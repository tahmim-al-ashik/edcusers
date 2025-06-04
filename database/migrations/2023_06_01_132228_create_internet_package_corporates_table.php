<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInternetPackageCorporatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('internet_package_corporates', function (Blueprint $table) {
            $table->id();
            $table->string('package_name')->comment('For radius/mikrotik');
            $table->text('package_type')->comment('Wifi/Broadband');
            $table->integer('client_id')->comment('Package for specific partner');
            $table->text('en_title');
            $table->text('bn_title');
            $table->float('price');
            $table->integer('expiration')->comment('Duration In minutes');
            $table->integer('is_active')->default(1);
            $table->integer('weight')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('internet_package_corporates');
    }
}
