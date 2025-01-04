<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ip_address');
            $table->string('line_code');
            $table->double('latitude');
            $table->double('longitude');
            $table->string('device_type');
            $table->string('status');
            $table->bigInteger('response_time');
            $table->integer('count');
            $table->unsignedBigInteger('group_id');
            $table->timestamp('online_since')->nullable();
            $table->timestamp('offline_since')->nullable();
            $table->timestamp('last_examination_date')->nullable();
            $table->unsignedBigInteger('insert_user_id');
            $table->timestamps();
            $table->unsignedBigInteger('delete_user_id')->nullable();
            $table->timestamp('delete_date')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('devices');
    }
};
