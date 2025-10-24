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
        $oldTables = $this->getTables();

        foreach ($oldTables as $table) {
            cache()->forget($this->getCachePrefix().$table.'_columns');
            cache()->forget($this->getCachePrefix().$table.'_column_types');
            cache()->forget($this->getCachePrefix().$table.'_forward_keys');
            cache()->forget($this->getCachePrefix().$table.'_reverse_keys');
        }

        cache()->forget($this->getCachePrefix().'tables');
        cache()->forget($this->getCachePrefix().'foreign_keys');

        if (app()->runningInConsole()) {
            echo '⚙️  Rebuilding database cache schema...' . PHP_EOL;
        }

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
                $schema = config("database.connections.$this->defaultConnection.schema", 'public');
                $results = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = ?", [$schema]);
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

            if ($driver === 'sqlite') {
                $tables = $this->getTables();
                $foreignKeys = [];
                foreach ($tables as $table) {
                    $results = DB::select("PRAGMA foreign_key_list('$table')");
                    foreach ($results as $row) {
                        $foreignKeys[] = [
                            'constraint_name' => "fk_{$table}_{$row->id}",
                            'table_name' => $table,
                            'column_name' => $row->from,
                            'referenced_table_name' => $row->table,
                            'referenced_column_name' => $row->to,
                        ];
                    }
                }
                return $foreignKeys;
            }

            throw new \RuntimeException("Unsupported driver: {$driver}");
        });
    }

    public function buildTableStructure(): void
    {
        $tables = $this->getTables();
        $this->getForeignKeys();
        $total = count($tables);

        if (app()->runningInConsole()) {
            echo "Processing $total tables..." . PHP_EOL;
        }

        foreach ($tables as $index => $table) {
            $columns = $this->getColumns($table);
            $this->getColumnTypes($table);

            if (app()->runningInConsole()) {
                echo "     \033[32m (" . ($index + 1) . "/$total) $table\033[0m [" . implode(', ', $columns) . "] " . PHP_EOL;
            }
        }
    }

    public function getForward(string $table): array
    {
        return cache()->rememberForever($this->getCachePrefix().$table.'_forward_keys', function () use ($table) {
            $results = [];
            $foreignKeys = $this->getForeignKeys(); // Gets from global FK cache
            foreach ($foreignKeys as $fk) {
                if ($fk['table_name'] === $table) {
                    $results[] = $fk;
                }
            }

            return $results;
        });
    }

    public function getReverse(string $table): array
    {
        return cache()->rememberForever($this->getCachePrefix().$table.'_reverse_keys', function () use ($table) {
            $results = [];
            $foreignKeys = $this->getForeignKeys(); // Gets from global FK cache
            foreach ($foreignKeys as $fk) {
                if ($fk['referenced_table_name'] === $table) {
                    $results[] = $fk;
                }
            }

            return $results;
        });
    }
}
