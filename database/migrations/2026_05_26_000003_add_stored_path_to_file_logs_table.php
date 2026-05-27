<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('file_logs', 'stored_path')) {
            return;
        }

        Schema::table('file_logs', function (Blueprint $table) {
            $table->string('stored_path')->nullable()->after('output_filename');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('file_logs', 'stored_path')) {
            return;
        }

        Schema::table('file_logs', function (Blueprint $table) {
            $table->dropColumn('stored_path');
        });
    }
};
