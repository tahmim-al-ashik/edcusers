<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInternetPackagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('internet_packages', function (Blueprint $table) {
            $table->id();
            $table->string('mikrotik_radius_group_name')->comment('For radius/mikrotik');
            $table->text('en_title');
            $table->text('bn_title');
            $table->text('type')->comment('Wifi/Broadband');
            $table->text('zone_id')->nullable()->comment('Package for specific partner');
            $table->float('price');
            $table->integer('expiration')->comment('Duration In minutes');
            $table->float('sales_point_commission')->default(0.0);
            $table->float('sales_agent_commission')->default(0.0);
            $table->string('commission_type')->default('percentage');
            $table->float('user_points')->comment('Points for new users');
            $table->boolean('is_active')->default(false);
            $table->boolean('skip_from_display')->default(true);
            $table->integer('weight')->default(0);
            $table->text('bg_image_source')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('internet_packages');
    }
}
