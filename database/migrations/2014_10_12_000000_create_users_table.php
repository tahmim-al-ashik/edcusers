<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('auth_id')->unique();
            $table->string('status')->default('active'); // pending,active,block,suspend,deleted
            $table->boolean('panel_access')->default(false);
            $table->string('base_role')->default('user');
            $table->string('password');
            $table->string('text_password')->nullable();
            $table->mediumText('permissions')->nullable();
            $table->timestamp('last_access')->useCurrent();
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
        Schema::dropIfExists('users');
    }
}
