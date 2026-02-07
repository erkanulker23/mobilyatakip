<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        if (!Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 50)->default('admin')->after('email');
            });
        }

        if (!Schema::hasColumn('users', 'isActive')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('isActive')->default(true)->after('role');
            });
        }

        if (!Schema::hasColumn('users', 'passwordHash')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('passwordHash', 255)->nullable()->after('password');
            });
            // Mevcut Laravel "password" kolonunu passwordHash'e kopyala (tek seferlik)
            $rows = \DB::table('users')->whereNotNull('password')->get();
            foreach ($rows as $row) {
                \DB::table('users')->where('id', $row->id)->update(['passwordHash' => $row->password]);
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
            if (Schema::hasColumn('users', 'isActive')) {
                $table->dropColumn('isActive');
            }
            if (Schema::hasColumn('users', 'passwordHash')) {
                $table->dropColumn('passwordHash');
            }
        });
    }
};
