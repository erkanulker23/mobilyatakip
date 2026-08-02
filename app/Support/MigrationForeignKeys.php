<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

final class MigrationForeignKeys
{
    public static function dropOnColumn(string $table, string $column): void
    {
        $fks = DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
            [DB::getDatabaseName(), $table, $column]
        );

        foreach ($fks as $fk) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }
    }

    public static function columnType(string $table, string $column): ?string
    {
        $row = DB::selectOne(
            'SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [DB::getDatabaseName(), $table, $column]
        );

        return $row->COLUMN_TYPE ?? null;
    }

    public static function alignColumn(string $table, string $column, string $referenceTable, string $referenceColumn, bool $nullable = true): ?string
    {
        $referenceType = self::columnType($referenceTable, $referenceColumn);
        if (! $referenceType) {
            return null;
        }

        $nullSql = $nullable ? 'NULL' : 'NOT NULL';
        DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` {$referenceType} {$nullSql}");

        return $referenceType;
    }

    public static function addIfMissing(
        string $table,
        string $column,
        string $referenceTable,
        string $referenceColumn,
        string $constraintName,
        string $onDelete = 'SET NULL'
    ): bool {
        $existing = DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
            [DB::getDatabaseName(), $table, $column]
        );

        if (! empty($existing)) {
            return true;
        }

        try {
            DB::statement(
                "ALTER TABLE `{$table}` ADD CONSTRAINT `{$constraintName}` FOREIGN KEY (`{$column}`) REFERENCES `{$referenceTable}`(`{$referenceColumn}`) ON DELETE {$onDelete}"
            );

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
