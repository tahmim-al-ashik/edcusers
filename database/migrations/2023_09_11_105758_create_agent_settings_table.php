<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgentSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agent_settings', function (Blueprint $table) {
            $table->id();
            $table->float("sp_ipmc")->default(3); // sales_point_internet_package_monthly_commission_rate
            $table->float("sa_ipmc")->default(2); // sales_agent_internet_package_monthly_commission_rate
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('agent_settings');
    }
}
