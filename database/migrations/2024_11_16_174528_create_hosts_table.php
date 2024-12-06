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
        Schema::create('hosts', function (Blueprint $table) {
            $table->id(); // Auto-incremented primary key
            $table->string('hostname');
            $table->string('host_ip');
            $table->unsignedBigInteger('group_id');
            $table->string('group_title');
            $table->double('lat');
            $table->double('lng');
            $table->enum('status', ['working', 'off_less_24h', 'off_more_24h'])->default('working');
            $table->integer('count')->default(0);
            $table->string('server'); // Adjust based on HostServer enum
            $table->timestamp('last_examination_date')->nullable();
            $table->string('insert_type');
            $table->unsignedBigInteger('insert_user_id');
            $table->string('insert_user_name');
            $table->timestamp('insert_date')->useCurrent();
            $table->timestamps(); // Created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hosts');
    }
};
