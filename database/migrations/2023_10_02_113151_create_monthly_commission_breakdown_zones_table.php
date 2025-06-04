<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMonthlyCommissionBreakdownZonesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('monthly_commission_breakdown_zones', function (Blueprint $table) {
            $table->id();
            $table->text('zone_id');
            $table->date('date_month');
            $table->float('commission_rate_wifi');
            $table->float('commission_rate_broadband');
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
        Schema::dropIfExists('monthly_commission_breakdown_zones');
    }
}
