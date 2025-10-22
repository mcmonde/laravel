<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSchemaCacheService
{
    protected string $defaultPrefix;
    protected string $defaultDriver;
    protected string $defaultConnection;
    protected array $driverCache = [];
    protected array $prefixCache = [];
    protected array $visitedConstraints = [];

    public function __construct()
    {
        $this->defaultPrefix = config('cache.prefix', 'db_schema_');
        $this->defaultConnection = config('database.default');
        $this->defaultDriver = config("database.connections.{$this->defaultConnection}.driver", 'mysql');

        $this->driverCache[$this->defaultConnection] = $this->defaultDriver;
        $this->prefixCache[$this->defaultConnection] = $this->buildPrefix($this->defaultConnection);
    }

    public function getDriver(?string $connection = null): string
    {
        $connection = $connection ?? $this->defaultConnection;

        if (isset($this->driverCache[$connection])) {
            return $this->driverCache[$connection];
        }

        return $this->driverCache[$connection] =
            config("database.connections.$connection.driver", $this->defaultDriver);
    }

    public function getCachePrefix(?string $connection = null): string
    {
        $connection = $connection ?? $this->defaultConnection;

        if (isset($this->prefixCache[$connection])) {
            return $this->prefixCache[$connection];
        }

        return $this->prefixCache[$connection] = $this->buildPrefix($connection);
    }

    protected function buildPrefix(string $connection): string
    {
        $customPrefix = config("database.connections.$connection.cache_prefix");

        if ($customPrefix) {
            return $customPrefix;
        }

        return $this->defaultPrefix . $connection . '_';
    }

    public function refresh(): void
    {
        foreach ($this->getTables() as $table) {
            cache()->forget($this->getCachePrefix().$table.'_columns');
            cache()->forget($this->getCachePrefix().$table.'_column_types');
            cache()->forget($this->getCachePrefix().$table.'_structure');
        }

        cache()->forget($this->getCachePrefix().'tables');
        cache()->forget($this->getCachePrefix().'foreign_keys');

        $this->buildTableStructure();
    }

    public function getTables(): array
    {
        return cache()->rememberForever($this->getCachePrefix()."tables", function () {
            $driver = $this->getDriver();

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
                return array_map(fn($row) => $row->name, $results);
            }

            throw new \RuntimeException("Unsupported driver: {$driver}");
        });
    }

    public function getColumns(string $table): array
    {
        return cache()->rememberForever($this->getCachePrefix().$table.'_columns', function () use ($table) {
            return Schema::getColumnListing($table);
        });
    }

    public function getColumnTypes(string $table): array
    {
        return cache()->rememberForever($this->getCachePrefix().$table.'_column_types', function () use ($table) {
            $types = [];
            foreach ($this->getColumns($table) as $col) {
                $types[$col] = Schema::getColumnType($table, $col);
            }
            return $types;
        });
    }

    public function isSoftDeletable(string $table): bool
    {
        $columns = $this->getColumns($table);
        return in_array('deleted_at', $columns, true);
    }

    public function getForeignKeys(): array
    {
        return cache()->rememberForever($this->getCachePrefix().'foreign_keys', function () {
            $driver = $this->getDriver();

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

    public function buildTableStructure(): void
    {
        $tables = $this->getTables();
        $this->getForeignKeys();

        foreach ($tables as $table) {
            $this->getColumns($table);
            $this->getColumnTypes($table);
        }
    }

    public function traverseTableBFS(string $table, ?int $maxDepth = null, int $maxNodes = 10000, int $maxMemoryMB = 128, ?string $direction = null): array
    {

        $foreignKeys = $this->getForeignKeys();
        //start with the base table
        $results[$table] = [
            'type'              => 'base',
//                        'table'             => $fk['table_name'],
//                        'column'            => $fk['column_name'],
//                        'referenced_table'  => $fk['referenced_table_name'],
//                        'referenced_column' => $fk['referenced_column_name'],
            'columns'           => $this->getColumns($table),
            'depth' => 0,
            'children' => []
        ];

        $queue = [
            [
                'table'     => $table,
                'depth'     => 1,
                'parentKey' => null,
            ]
        ];

        $nodesProcessed = 0;
        $aborted = false;
        $abortReason = null;

        while (!empty($queue)) {
            $current = array_shift($queue);
            $currentTable = $current['table'];
            $depth = $current['depth'];

            // ✅ Safeguards
            if (++$nodesProcessed > $maxNodes) {
                $aborted = true;
                $abortReason = "Traversal aborted: exceeded maxNodes ($maxNodes).";
                break;
            }

            if (memory_get_usage(true) / 1024 / 1024 > $maxMemoryMB) {
                $aborted = true;
                $abortReason = "Traversal aborted: exceeded memory limit of {$maxMemoryMB}MB.";
                break;
            }

            // Stop if maxDepth reached
            if ($maxDepth !== null && $depth > $maxDepth) {
                continue;
            }

            foreach ($foreignKeys as $fk) {
                // ✅ Forward FK (table → referenced_table)
                if (($direction === null || $direction === 'forward')
                    && $fk['table_name'] === $currentTable
                    && !in_array($fk['constraint_name'], $this->visitedConstraints)) {

                    $this->visitedConstraints[] = $fk['constraint_name'];

                    $node = [
                        'type'              => 'forward',
//                        'table'             => $fk['table_name'],
//                        'column'            => $fk['column_name'],
//                        'referenced_table'  => $fk['referenced_table_name'],
//                        'referenced_column' => $fk['referenced_column_name'],
                        'columns'           => $this->getColumns($fk['referenced_table_name']),
                        'depth' => $depth,
                        'children' => []
                    ];

//                    if ($maxDepth !== 0) {
//                        $node['depth'] = $depth;
//                        $node['children'] = [];
//                    }

                    $this->attachNode($results, $current['parentKey'], $fk['constraint_name'], $node);

                    $queue[] = [
                        'table'     => $fk['referenced_table_name'],
                        'depth'     => $depth + 1,
                        'parentKey' => $fk['constraint_name'],
                    ];
                }

                // ✅ Reverse FK (referenced_table ← table)
                if (($direction === null || $direction === 'reverse')
                    && $fk['referenced_table_name'] === $currentTable
                    && !in_array($fk['constraint_name'], $this->visitedConstraints)) {

                    $this->visitedConstraints[] = $fk['constraint_name'];

                    $node = [
                        'type'              => 'reverse',
//                        'table'             => $fk['table_name'],
//                        'column'            => $fk['column_name'],
//                        'referenced_table'  => $fk['referenced_table_name'],
//                        'referenced_column' => $fk['referenced_column_name'],
                        'columns'           => $this->getColumns($fk['table_name']),
                        'depth' => $depth,
                        'children' => []
                    ];

//                    if ($maxDepth !== 0) {
//                        $node['depth'] = $depth;
//                        $node['children'] = [];
//                    }

                    $this->attachNode($results, $current['parentKey'], $fk['constraint_name'], $node);

                    $queue[] = [
                        'table'     => $fk['table_name'],
                        'depth'     => $depth + 1,
                        'parentKey' => $fk['constraint_name'],
                    ];
                }
            }
        }

        // ✅ Add meta only if traversal was aborted
        if ($aborted) {
            $results['_meta'] = [
                'status' => 'aborted',
                'reason' => $abortReason,
                'processed_nodes' => $nodesProcessed,
//                'memory_usage_bytes' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'memory_usage_bytes' => memory_get_usage(true),
            ];
        }

        return $results;
    }

    /**
     * Helper to attach a node to the tree by parentKey.
     */
    protected function attachNode(array &$results, ?string $parentKey, string $constraintName, array $node): void
    {
        if ($parentKey === null) {
            $results[$constraintName] = $node;
            return;
        }

        $iterator = function (&$arr) use (&$iterator, $parentKey, $constraintName, $node) {
            foreach ($arr as $key => &$value) {
                if ($key === $parentKey) {
                    $value['children'][$constraintName] = $node;
                    return true;
                }
                if (!empty($value['children']) && $iterator($value['children'])) {
                    return true;
                }
            }
            return false;
        };

        $iterator($results);
    }

    public function getForward(string $table): array
    {
        $results = [];
        $foreignKeys = $this->getForeignKeys();
        foreach ($foreignKeys as $fk) {
            if ($fk['table_name'] === $table) {
                $results[] = $fk;
            }
        }

        return $results;
    }

    public function getReverse(string $table): array
    {
        $results = [];
        $foreignKeys = $this->getForeignKeys();
        foreach ($foreignKeys as $fk) {
            if ($fk['referenced_table_name'] === $table) {
                $results[] = $fk;
            }
        }

        return $results;
    }
}
