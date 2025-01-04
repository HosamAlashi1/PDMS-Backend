<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->unsignedBigInteger('parent_id')->nullable(); // Allow null for parent_id
            $table->integer('order')->nullable(); // Optional: Order can also be nullable
            $table->timestamps();
        });

    }

    public function down()
    {
        Schema::dropIfExists('permissions');
    }
};
