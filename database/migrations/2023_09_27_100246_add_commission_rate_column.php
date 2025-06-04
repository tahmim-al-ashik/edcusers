<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCommissionRateColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('network_support_centers', function (Blueprint $table) {
            // Remove "->after('zone_password')" so these columns are simply appended
            $table->string('commission_rate_type')->default('auto'); // auto = calculate on users, fixed
            $table->float('broadband_commission_rate')->default(0);
            $table->float('wifi_commission_rate')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('network_support_centers', function (Blueprint $table) {
            $table->dropColumn('commission_rate_type');
            $table->dropColumn('broadband_commission_rate');
            $table->dropColumn('wifi_commission_rate');
        });
    }
}
