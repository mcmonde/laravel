<?php

namespace App\Traits;

use App\Services\DatabaseSchemaCacheService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait HasDynamicRelations
{
    /**
     * In-memory cache for dynamic relation configurations.
     * @var array
     */
    protected static array $dynamicRelations = [];

    /**
     * In-memory cache of the application's [table => model] map.
     * @var array
     */
    protected static array $tableModelMapCache = [];

    /**
     * Boot the dynamic relations logic.
     * This runs ONCE per model class.
     */
    public static function bootHasDynamicRelations(): void
    {
        $class = static::class;
        if (isset(static::$dynamicRelations[$class])) {
            return;
        }

        $instance = new static;
        $table = $instance->getTable();
        $schema = app(DatabaseSchemaCacheService::class);

        // --- Load the global table-to-model map ONCE ---
        if (empty(static::$tableModelMapCache)) {
            static::$tableModelMapCache = $schema->getModelTableMap();
        }

        $relations = [];

        try {
            // --- 1. Process Forward Relations (belongsTo) ---
            $forwardKeys = $schema->getForward($table);
            foreach ($forwardKeys as $fk) {
                $relationName = Str::camel(Str::beforeLast($fk['column_name'], '_id'));
                $relatedModel = $instance->findModelClass($fk['referenced_table_name']);

                if ($relatedModel && !method_exists($instance, $relationName)) {
                    $relations[$relationName] = [
                        'type'        => 'belongsTo',
                        'model'       => $relatedModel,
                        'foreign_key' => $fk['column_name'],
                        'local_key'   => $fk['referenced_column_name'],
                    ];
                }
            }

            // --- 2. Process Reverse Relations (hasMany) ---
            $reverseKeys = $schema->getReverse($table);
            $modelNameSingular = Str::singular(Str::studly($table));
            $standardFkName = Str::snake($modelNameSingular) . '_id';

            foreach ($reverseKeys as $fk) {
                $relatedModel = $instance->findModelClass($fk['table_name']);
                if (!$relatedModel) {
                    continue;
                }

                $relationName = '';
                if ($fk['column_name'] === $standardFkName) {
                    // Standard: 'user_id' on 'posts' table -> posts()
                    $relationName = Str::camel(Str::plural($fk['table_name']));
                } else {
                    // Custom: 'author_id' on 'posts' table -> authorPosts()
                    $prefix = Str::camel(Str::beforeLast($fk['column_name'], '_id'));
                    $suffix = Str::plural(Str::studly($fk['table_name']));
                    $relationName = $prefix . $suffix;
                }

                if ($relationName && !method_exists($instance, $relationName)) {
                    $relations[$relationName] = [
                        'type'        => 'hasMany',
                        'model'       => $relatedModel,
                        'foreign_key' => $fk['column_name'],
                        'local_key'   => $fk['referenced_column_name'],
                    ];
                }
            }

        } catch (\Throwable $e) {
            Log::warning("Could not build dynamic relations for $class: " . $e->getMessage());
        }

        static::$dynamicRelations[$class] = $relations;
    }

    /**
     * Helper to find a model class name from a table name
     * using the dynamically built map.
     */
    protected function findModelClass(string $tableName): ?string
    {
        return static::$tableModelMapCache[$tableName] ?? null;
    }

    /**
     * Magic method to intercept calls to dynamic relations.
     */
    public function __call($method, $parameters)
    {
        // Check our static cache first
        $relations = static::$dynamicRelations[static::class] ?? [];
        if (array_key_exists($method, $relations)) {
            return $this->buildDynamicRelation($relations[$method]);
        }

        // Fallback to parent
        return parent::__call($method, $parameters);
    }

    /**
     * Factory to build the actual relation object.
     */
    protected function buildDynamicRelation(array $config)
    {
        return match ($config['type']) {
            'belongsTo' => $this->belongsTo(
                $config['model'],
                $config['foreign_key'],
                $config['local_key']
            ),
            'hasMany' => $this->hasMany(
                $config['model'],
                $config['foreign_key'],
                $config['local_key']
            ),
            default => null,
        };
    }

    public function getDynamicRelations(): array
    {
        // Ensures the boot method has run and populated the cache
        static::bootHasDynamicRelations();

        // Return the cached relations for this specific class
        return static::$dynamicRelations[static::class] ?? [];
    }
}
