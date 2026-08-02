<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class UserSchema
{
    private static ?bool $idIsUuid = null;

    private static ?string $createdAtColumn = null;

    private static ?string $updatedAtColumn = null;

    public static function idIsUuid(): bool
    {
        if (self::$idIsUuid !== null) {
            return self::$idIsUuid;
        }

        if (! Schema::hasTable('users')) {
            return self::$idIsUuid = true;
        }

        $row = DB::selectOne(
            'SELECT DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [DB::getDatabaseName(), 'users', 'id']
        );

        return self::$idIsUuid = ! in_array($row->DATA_TYPE ?? '', ['bigint', 'int', 'mediumint', 'smallint', 'tinyint'], true);
    }

    public static function createdAtColumn(): ?string
    {
        if (self::$createdAtColumn !== null) {
            return self::$createdAtColumn ?: null;
        }

        self::$createdAtColumn = '';
        if (Schema::hasTable('users')) {
            if (Schema::hasColumn('users', 'createdAt')) {
                self::$createdAtColumn = 'createdAt';
            } elseif (Schema::hasColumn('users', 'created_at')) {
                self::$createdAtColumn = 'created_at';
            }
        }

        return self::$createdAtColumn ?: null;
    }

    public static function updatedAtColumn(): ?string
    {
        if (self::$updatedAtColumn !== null) {
            return self::$updatedAtColumn ?: null;
        }

        self::$updatedAtColumn = '';
        if (Schema::hasTable('users')) {
            if (Schema::hasColumn('users', 'updatedAt')) {
                self::$updatedAtColumn = 'updatedAt';
            } elseif (Schema::hasColumn('users', 'updated_at')) {
                self::$updatedAtColumn = 'updated_at';
            }
        }

        return self::$updatedAtColumn ?: null;
    }
}
