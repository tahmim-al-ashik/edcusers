<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSchoolProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('school_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('uid')->foreign()->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('manager_id')->foreign()->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->string('school_name', 150);
            $table->string('edc_book_sl_no', 50);
            $table->string('connection_code', 255)->nullable();
            $table->integer('package_id')->nullable();
            $table->enum('electricity', ['yes', 'no', 'others'])->nullable();
            $table->string('area_code', 50)->nullable();
            $table->string('dis_code', 50)->nullable();
            $table->string('emis_code', 50)->nullable();
            $table->string('head_teacher_name', 150)->nullable();
            $table->string('head_teacher_mobile', 15)->nullable();
            $table->string('head_teacher_ast_name', 150)->nullable();
            $table->string('head_teacher_ast_mobile', 15)->nullable();
            $table->string('fiber_id', 50)->nullable();
            $table->integer('fiber_core')->nullable();
            $table->integer('db_signal')->nullable();
            $table->integer('start_meter')->nullable();
            $table->integer('end_meter')->nullable();
            $table->integer('fiber_length')->nullable();
            $table->string('onu_mac', 50);
            $table->string('router_username', 50);
            $table->string('router_password', 50);
            $table->string('router_mac', 50);
            $table->string('router_remote_magt_port', 50);
            $table->string('gateway', 50);
            $table->string('subnet_mask', 50);
            $table->string('dnsv4_primary', 50);
            $table->string('dnsv4_secondary', 50);
            $table->string('ipv4_ip', 50);
            $table->string('ipv6_ip', 50);
            $table->string('snmp_com', 50)->nullable();
            $table->enum('slaac_enabled', ['yes', 'no', 'others'])->nullable();
            $table->enum('icmp_enabled', ['yes', 'no', 'others'])->nullable();
            $table->string('router_model', 50);
            $table->string('router_serial', 50);
            $table->string('tj_box_quantity', 15);
            $table->string('tj_box_remarks', 100);
            $table->string('fiber_patch_cord_quantity', 15);
            $table->string('fiber_patch_cord_remarks', 100);
            $table->integer('installation_cost');
            $table->enum('status',['active','pending','inactive'])->nullable();
            $table->text('comments')->nullable();
            $table->text('others')->nullable();
            $table->unsignedBigInteger('updated_by')->foreign()->references('id')->on('users')->onDelete('cascade');
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
        Schema::dropIfExists('school_profiles');
    }
}
