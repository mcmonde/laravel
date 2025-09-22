<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSchemaCacheService
{
    protected string $cachePrefix;
    protected string $driver;

    public function __construct()
    {
        $this->driver = DB::getDriverName();
        $this->cachePrefix = env('CACHE_PREFIX', 'db_schema_');
    }

    /**
     * Get the full database structure from cache (or build it if not cached).
     */
    public function getTableStructure($table): array
    {
        return [
            'has_foreign_keys' => cache()->get($this->cachePrefix.$table.'_structure'),
            'belongs_to_foreign_keys' => $this->getReferencedBy($table),
        ];
    }

    /**
     * Forget and rebuild the cache.
     */
    public function refresh(): array
    {
        Cache::forget($this->cachePrefix);
        return $this->get();
    }

    /**
     * Build the database structure (tables, columns, relations, sub-relations).
     */
    public function buildTableStructure(): void
    {
        $tables = $this->getTables();
        $foreignKeys = $this->getForeignKeys();

        foreach ($tables as $table) {
            cache()->rememberForever($this->cachePrefix.$table.'_structure', function () use ($table, $foreignKeys) {
                return [
                    $table => $this->getColumns($table),
                    'relations' => $this->getRelationsRecursive($table, $foreignKeys),
                ];
            });
        }
    }

    /**
     * Get all tables in the database.
     */
    public function getTables(): array
    {
        return cache()->rememberForever($this->cachePrefix."tables", function () {
            $driver = $this->driver;

            if ($driver === 'mysql') {
                $results = DB::select('SHOW TABLES');
                $key = 'Tables_in_' . DB::getDatabaseName();

                return array_map(fn($row) => $row->$key, $results);
            }

            if ($driver === 'pgsql') {
                $results = DB::select("SELECT tablename FROM pg_tables WHERE schemaname='public'");
                return array_map(fn($row) => $row->tablename, $results);
            }

            if ($driver === 'sqlite') {
                $results = DB::select("SELECT name FROM sqlite_master WHERE type='table'");
                return array_map(fn($row) => $row->tablename, $results);
            }

            throw new \RuntimeException("Unsupported driver: {$driver}");
        });
    }

    /**
     * Get all columns for a table.
     */
    public function getColumns(string $table): array
    {
        return cache()->rememberForever($this->cachePrefix.$table.'_columns', function () use ($table) {
            return Schema::getColumnListing($table);
        });
    }

    /**
     * Get all foreign key relationships.
     */
    public function getForeignKeys(): array
    {
        return cache()->rememberForever($this->cachePrefix.'foreign_keys', function () {
            $driver = $this->driver;

            if ($driver === 'mysql') {
                $results = DB::select("
                    SELECT
                        kcu.CONSTRAINT_NAME,
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
                    return [
                        'constraint_name' => $row->CONSTRAINT_NAME,
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
                return array_map(function ($row) {
                    return [
                        'constraint_name' => $row->constraint_name,
                        'table_name' => $row->table_name,
                        'column_name' => $row->column_name,
                        'referenced_table_name' => $row->referenced_table,
                        'referenced_column_name' => $row->referenced_column,
                    ];
                }, $results);
            }

            throw new \RuntimeException("Unsupported driver: {$driver}");
        });
    }

    public function getReferencedBy(string $table): array
    {
        $foreignKeys = $this->getForeignKeys();
        $referencedTables = [];
        foreach ($foreignKeys as $foreignKey) {
            if ($foreignKey['referenced_table_name'] === $table) {
                $referencedTables[] = $foreignKey;
            }
        }

        $referencedStructures = [];
        foreach ($referencedTables as $referencedTable) {
            $referencedStructures[] = cache()->get($this->cachePrefix.$referencedTable['table_name'].'_structure');
        }

        return $referencedStructures;
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
            if ($relation['table_name'] === $table) {
                $relations[] = [
//                    'local_column'   => $relation['column_name'],
//                    'foreign_table'  => $relation['referenced_table_name'],
//                    'foreign_column' => $relation['referenced_column_name'],
//                    'constraint_name' => $relation['constraint_name'],
                    $relation['constraint_name'] => $this->getColumns($relation['referenced_table_name']),
                    'relations'  => $this->getRelationsRecursive(
                        $relation['referenced_table_name'],
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
