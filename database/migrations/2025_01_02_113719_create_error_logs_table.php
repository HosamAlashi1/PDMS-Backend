<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();
            $table->string('message');
            $table->text('stack_trace');
            $table->string('request_path');
            $table->string('query_params');
            $table->string('http_method');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('insert_date');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('error_logs');
    }
};
