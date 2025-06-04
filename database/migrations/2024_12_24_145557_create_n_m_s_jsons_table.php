<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNMSJsonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('n_m_s_jsons', function (Blueprint $table) {
            $table->id();
            $table->integer('lot_id')->nullable();
            $table->integer('lot_uid')->nullable();
            $table->string('institution_type', 50)->nullable();
            $table->mediumText('file')->nullable();
            $table->string('created_by', 50)->nullable();
            $table->string('updated_by', 50)->nullable();
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
        Schema::dropIfExists('n_m_s_jsons');
    }
}
