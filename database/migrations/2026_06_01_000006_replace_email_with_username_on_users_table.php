<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username')->nullable()->unique()->after('name');
            });

            $usedUsernames = [];

            foreach (DB::table('users')->orderBy('id')->get() as $user) {
                $source = property_exists($user, 'email')
                    ? Str::before((string) $user->email, '@')
                    : (string) $user->name;
                $baseUsername = Str::slug($source, '_') ?: 'user_'.$user->id;
                $username = $baseUsername;
                $suffix = 2;

                while (isset($usedUsernames[$username])) {
                    $username = $baseUsername.'_'.$suffix;
                    $suffix++;
                }

                $usedUsernames[$username] = true;

                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['username' => $username]);
            }

            Schema::table('users', function (Blueprint $table) {
                $table->string('username')->nullable(false)->change();
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasIndex('users', ['email'])) {
                $table->dropUnique(['email']);
            }

            $legacyColumns = array_values(array_filter([
                'email_verified_at',
                'email',
            ], fn (string $column): bool => Schema::hasColumn('users', $column)));

            if ($legacyColumns !== []) {
                $table->dropColumn($legacyColumns);
            }
        });

        Schema::dropIfExists('password_reset_tokens');

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('username')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->unique()->after('name');
            $table->timestamp('email_verified_at')->nullable()->after('email');
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });

        Schema::dropIfExists('password_reset_tokens');

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }
};
