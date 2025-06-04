<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessageAndNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('message_and_notifications', function (Blueprint $table) {
            $table->id();
            $table->integer('uid');
            $table->text('title');
            $table->mediumText('description')->nullable();
            $table->integer('sender_uid')->default(1);
            $table->integer('is_read')->default(0);
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
        Schema::dropIfExists('message_and_notifications');
    }
}
