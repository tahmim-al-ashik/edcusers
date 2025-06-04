<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionPanelMoneyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_panel_money', function (Blueprint $table) {
            $table->id();
            $table->double('amount', 8, 2); // Example: 123456.78
            $table->Integer('sender_uid');
            $table->Integer('receiver_uid');
            $table->string('trx_id', 256); // Example: "TRX123456789"
            $table->string('invoice_number', 256); // Example: "INV2024001"
            $table->string('payment_id', 256); // Example: "PAYMENT123"
            $table->string('type', 256); // Example: "credit", "debit"
            $table->string('status', 256); // Example: "pending", "completed", "cancelled"
            $table->text('remarks')->nullable(); // Nullable for optional remarks
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
        Schema::dropIfExists('transaction_panel_money');
    }
}
