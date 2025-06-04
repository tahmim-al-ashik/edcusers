<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateErrorLogPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('error_log_payments', function (Blueprint $table) {
            $table->id();
            $table->text('auth_id');
            $table->integer('uid');
            $table->text('zone_id')->nullable();
            $table->text('trx_id');
            $table->text('error_type');
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
        Schema::dropIfExists('error_log_payments');
    }
}
