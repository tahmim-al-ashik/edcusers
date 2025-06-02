<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    echo "Running routers table migration...\n";

    Schema::create('routers', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('ip_address');
        $table->string('username');
        $table->string('password');
        $table->integer('port')->default(8728);
        $table->text('description')->nullable();
        $table->string('location')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routers');
    }
};
