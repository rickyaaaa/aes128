<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crypto_process_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('file_log_id')->nullable()->constrained()->nullOnDelete();
            $table->string('operation', 16);
            $table->string('file_name');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('file_type', 32)->nullable();
            $table->decimal('execution_time_seconds', 10, 4);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['operation', 'created_at']);
            $table->index(['user_id', 'operation', 'created_at']);
            $table->index(['file_log_id', 'operation', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crypto_process_logs');
    }
};
