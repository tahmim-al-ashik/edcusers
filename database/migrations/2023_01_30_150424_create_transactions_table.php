<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->text("trx_id");
            $table->text("trx_type");//wallet_point,payment
            $table->text("plus_minus");//plus,minus
            $table->integer("sender_uid");
            $table->integer("receiver_uid");
            $table->text("reference")->nullable();
            $table->text("method");
            $table->float("amount");
            $table->text("purpose");
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
        Schema::dropIfExists('transactions');
    }
}
