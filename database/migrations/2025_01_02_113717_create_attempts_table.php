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
        Schema::create('attempts', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address');
            $table->string('status');
            $table->bigInteger('response_time');
            $table->boolean('is_alert_sent');
            $table->timestamp('alert_sent_date')->nullable(); // Allows null values
            $table->timestamp('examination_date')->nullable(); // Allows null values
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('attempts');
    }
};
