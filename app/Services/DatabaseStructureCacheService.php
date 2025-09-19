<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseStructureCacheService
{
    protected string $cacheKey = 'db:structure';

    /**
     * Get the full database structure from cache (or build it if not cached).
     */
    public function get(): array
    {
        return Cache::rememberForever($this->cacheKey, function () {
            return $this->buildStructure();
        });
    }

    /**
     * Forget and rebuild the cache.
     */
    public function refresh(): array
    {
        Cache::forget($this->cacheKey);
        return $this->get();
    }

    /**
     * Build the database structure (tables, columns, relations, sub-relations).
     */
    protected function buildStructure(): array
    {
        $tables = $this->getTables();
        $foreignKeys = $this->getForeignKeys();

        $structure = [];
        $tables = ['documents'];
        foreach ($tables as $table) {
            $structure[$table] = [
                'columns' => $this->getColumns($table),
                'relations' => $this->getRelationsRecursive($table, $foreignKeys),
            ];
        }

        return $structure;
    }

    /**
     * Get all tables in the database.
     */
    protected function getTables(): array
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            $results = DB::select('SHOW TABLES');
            $key = 'Tables_in_' . DB::getDatabaseName();

            return array_map(fn($row) => $row->$key, $results);
        }

        if ($driver === 'pgsql') {
            $results = DB::select("SELECT tablename FROM pg_tables WHERE schemaname='public'");
            return array_map(fn($row) => $row->tablename, $results);
        }

        throw new \RuntimeException("Unsupported driver: {$driver}");
    }

    /**
     * Get all columns for a table.
     */
    protected function getColumns(string $table): array
    {
        return Schema::getColumnListing($table);
    }

    /**
     * Get all foreign key relationships.
     */
    protected function getForeignKeys(): array
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            $results = DB::select("
            SELECT
                kcu.TABLE_NAME,
                kcu.COLUMN_NAME,
                kcu.REFERENCED_TABLE_NAME,
                kcu.REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE kcu
            WHERE kcu.REFERENCED_TABLE_NAME IS NOT NULL
              AND kcu.CONSTRAINT_SCHEMA = DATABASE()
        ");

            // Normalize keys
            return array_map(function ($row) {
                return (object) [
                    'table_name' => $row->TABLE_NAME,
                    'column_name' => $row->COLUMN_NAME,
                    'referenced_table_name' => $row->REFERENCED_TABLE_NAME,
                    'referenced_column_name' => $row->REFERENCED_COLUMN_NAME,
                ];
            }, $results);
        }

        if ($driver === 'pgsql') {
            $results = DB::select("
            SELECT
                tc.constraint_name,
                tc.table_name,
                kcu.column_name,
                ccu.table_name AS referenced_table,
                ccu.column_name AS referenced_column
            FROM
                information_schema.table_constraints tc
            JOIN information_schema.key_column_usage kcu
                ON tc.constraint_name = kcu.constraint_name
            JOIN information_schema.constraint_column_usage ccu
                ON ccu.constraint_name = tc.constraint_name
            WHERE constraint_type = 'FOREIGN KEY'
        ");

            // Already lowercase, just return
            return $results;
        }

        throw new \RuntimeException("Unsupported driver: {$driver}");
    }

    /**
     * Recursively build relations for a given table.
     */
    protected function getRelationsRecursive(string $table, array $map, array &$visited = []): array
    {
        // If we already fully resolved this table, return it
        if (isset($visited[$table])) {
            return $visited[$table];
        }

        // Mark as "in progress" to prevent infinite recursion
        $visited[$table] = [];

        $relations = [];
        foreach ($map as $relation) {
            if ($relation->table_name === $table) {
                $relations[] = [
                    'local_column'   => $relation->column_name,
                    'foreign_table'  => $relation->referenced_table,
                    'foreign_column' => $relation->referenced_column,
                    'constraint_name' => $relation->constraint_name,
                    'sub_relations'  => $this->getRelationsRecursive(
                        $relation->referenced_table,
                        $map,
                        $visited
                    ),
                ];
            }
        }

        // Save the resolved relations
        return $visited[$table] = $relations;
    }
}
