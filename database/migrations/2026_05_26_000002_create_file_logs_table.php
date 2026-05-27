<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('file_type', 16);
            $table->string('process_type');
            $table->unsignedInteger('file_size_kb');
            $table->string('status')->default('success');
            $table->string('ip_address', 45)->nullable();
            $table->string('output_filename')->nullable();
            $table->string('stored_path')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['process_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_logs');
    }
};
