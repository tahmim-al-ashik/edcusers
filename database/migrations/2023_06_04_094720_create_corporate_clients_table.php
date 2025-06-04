<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCorporateClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('corporate_clients', function (Blueprint $table) {
            $table->id();
            $table->integer('uid');
            $table->text('name');
            $table->text('company_name');
            $table->text('type');
            $table->text('mobile_number');
            $table->text('email');
            $table->text('thana');
            $table->text('district');
            $table->text('division');
            $table->string('address');
            $table->text('mikrotik_ip');
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
        Schema::dropIfExists('corporate_clients');
    }
}
