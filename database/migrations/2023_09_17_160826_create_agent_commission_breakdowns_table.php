<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgentCommissionBreakdownsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agent_commission_breakdowns', function (Blueprint $table) {
            $table->id();
            $table->integer('agent_uid');
            $table->integer('user_uid');
            $table->float('previous_wallet_amount');
            $table->float('new_commission_amount');
            $table->timestamps();
            // $table->id();
            // $table->integer('agent_uid');
            // $table->integer('user_uid');
            // $table->text('trx_id')->nullable();
            // $table->float('payment_amount');
            // $table->float('commission_rate');
            // $table->float('commission_amount');
            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('agent_commission_breakdowns');
    }
}
