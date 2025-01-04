<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('color');
            $table->text('coordinates');
            $table->string('city');
            $table->string('governorate');
            $table->boolean('is_active');
            $table->boolean('is_delete');
            $table->unsignedBigInteger('insert_user_id');
            $table->timestamps();
            $table->unsignedBigInteger('delete_user_id')->nullable();
            $table->timestamp('delete_date')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('groups');
    }
};
