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
            $table->id(); // Auto-incremented primary key
            $table->string('hostname');
            $table->string('host_ip');
            $table->enum('status', ['working', 'off_less_24h', 'off_more_24h']); // Based on HostStatus enum
            $table->boolean('is_mail_sent')->default(false);
            $table->timestamp('examination_date')->useCurrent();
            $table->timestamps(); // Created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attempts');
    }
};
