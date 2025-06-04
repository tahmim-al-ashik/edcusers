<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->integer('is_test_mode')->default(0);
            $table->integer('uid');
            $table->text('vendor_name');
            $table->text('trx_id');
            $table->text('invoice_number');
            $table->float('amount');
            $table->integer('process_status')->default(0);
            $table->text('purpose');
            $table->text('package')->nullable();
            $table->text('payment_id');
            $table->text('transaction_status');
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
        Schema::dropIfExists('payments');
    }
}
