<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('file_logs')) {
            return;
        }

        Schema::table('file_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('file_logs', 'file_name')) {
                $table->string('file_name')->nullable()->after('user_id');
            }
            if (! Schema::hasColumn('file_logs', 'file_size')) {
                $table->unsignedBigInteger('file_size')->nullable()->after('stored_path');
            }
        });

        if (Schema::hasColumn('file_logs', 'original_filename')) {
            DB::table('file_logs')
                ->whereNull('file_name')
                ->update(['file_name' => DB::raw('original_filename')]);
        }

        if (Schema::hasColumn('file_logs', 'file_size_kb')) {
            DB::table('file_logs')
                ->whereNull('file_size')
                ->update(['file_size' => DB::raw('file_size_kb * 1024')]);
        }

        DB::table('file_logs')
            ->whereNull('file_name')
            ->update(['file_name' => 'encrypted-file.enc']);

        DB::table('file_logs')
            ->whereNull('file_size')
            ->update(['file_size' => 0]);
    }

    public function down(): void
    {
        // Backward normalization is intentionally skipped.
    }
};
