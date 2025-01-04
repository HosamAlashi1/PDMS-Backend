<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_delete');
            $table->unsignedBigInteger('insert_user_id')->nullable();
            $table->unsignedBigInteger('update_user_id')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('delete_user_id')->nullable();
            $table->timestamp('delete_date')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('roles');
    }
};
