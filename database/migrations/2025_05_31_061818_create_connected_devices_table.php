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
        Schema::create('connected_devices', function (Blueprint $table) {
    $table->id();
    $table->foreignId('router_id')->constrained()->onDelete('cascade');
    $table->string('mac_address');
    $table->string('ip_address');
    $table->string('hostname')->nullable();
    $table->string('interface');
    $table->bigInteger('bytes_in')->default(0);
    $table->bigInteger('bytes_out')->default(0);
    $table->boolean('active')->default(true);
    $table->timestamp('last_seen');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('connected_devices');
    }
};
