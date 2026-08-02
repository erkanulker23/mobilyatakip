<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class UserSchema
{
    public static function idIsUuid(): bool
    {
        if (! Schema::hasTable('users')) {
            return true;
        }

        $row = DB::selectOne(
            'SELECT DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [DB::getDatabaseName(), 'users', 'id']
        );

        return ! in_array($row->DATA_TYPE ?? '', ['bigint', 'int', 'mediumint', 'smallint', 'tinyint'], true);
    }
}
