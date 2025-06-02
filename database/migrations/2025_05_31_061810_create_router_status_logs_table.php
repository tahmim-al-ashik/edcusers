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
        Schema::create('router_status_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('router_id')->constrained()->onDelete('cascade');
    $table->boolean('online')->default(false);
    $table->float('cpu_load')->nullable();
    $table->float('memory_usage')->nullable();
    $table->bigInteger('total_bytes_in')->default(0);
    $table->bigInteger('total_bytes_out')->default(0);
    $table->integer('active_connections')->default(0);
    $table->timestamp('logged_at');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('router_status_logs');
    }
};
