<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGeoUnionPouroshovasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('geo_union_pouroshovas', function (Blueprint $table) {
            $table->id();
            $table->integer('pid');
            $table->text('bn_name');
            $table->text('en_name')->nullable(true);
            $table->text('area_type')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('geo_union_pouroshovas');
    }
}
