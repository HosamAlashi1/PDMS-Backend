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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('middle_name');
            $table->string('last_name');
            $table->string('personal_email');
            $table->string('company_email');
            $table->string('phone');
            $table->string('address');
            $table->string('password');
            $table->string('marital_status');
            $table->string('image')->nullable();
            $table->unsignedBigInteger('role_id');
            $table->boolean('receives_emails');
            $table->timestamp('last_email_sent');
            $table->integer('email_frequency_hours');
            $table->boolean('is_logout');
            $table->boolean('is_active');
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
        Schema::dropIfExists('users');
    }
};
