<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMonthlyCommissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('monthly_commissions', function (Blueprint $table) {
            $table->id();
            $table->integer('uid');
            $table->text('user_type');
            $table->date('date_month');
            $table->float('total_commission');
            $table->float('commission_rate');
            $table->float('commission_amount');
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
        Schema::dropIfExists('monthly_commissions');
    }
}
