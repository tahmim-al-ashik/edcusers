<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNetworkSupportCentersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('network_support_centers', function (Blueprint $table) {
            $table->id();
            $table->integer('uid');
            $table->text('zone_name')->nullable();
            $table->integer('is_test_mode')->default(0);
            $table->integer('simultaneous_use_disable')->default(0);
            $table->text('zone_id')->nullable();
            $table->text('zone_ip')->nullable();
            $table->text('center_type');// network_center, support_center
            $table->text('sub_centers')->nullable(); //[1,2,3]
            $table->integer('opening_package_id')->nullable();
            $table->text('coverage_type');
            $table->string('coverage_ids');
            $table->text('support_number')->nullable();
            $table->text('whatsapp_number')->nullable();
            $table->text('email')->nullable();
            $table->integer('division_id');
            $table->integer('district_id');
            $table->integer('upazila_id');
            $table->integer('union_id');
            $table->integer('village_id');
            $table->string('address')->nullable();
            $table->float('latitude', 8, 6);
            $table->float('longitude', 8, 6);
            $table->string('status')->default('pending'); // pending, trail, rejected, enlisted
            $table->longText('data_object');
            $table->text('updated_by');
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
        Schema::dropIfExists('network_support_centers');
    }
}
