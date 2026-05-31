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

        Schema::table('file_logs', function (Blueprint $table) {
            if (Schema::hasIndex('file_logs', ['process_type', 'status'])) {
                $table->dropIndex(['process_type', 'status']);
            }

            $legacyColumns = array_values(array_filter([
                'original_filename',
                'process_type',
                'file_size_kb',
                'status',
                'output_filename',
                'error_message',
            ], fn (string $column): bool => Schema::hasColumn('file_logs', $column)));

            if ($legacyColumns !== []) {
                $table->dropColumn($legacyColumns);
            }
        });
    }

    public function down(): void
    {
        // The legacy columns are no longer part of the file repository schema.
    }
};
