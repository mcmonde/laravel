<?php

namespace App\Traits;

use App\Services\DatabaseSchemaCacheService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait HasDynamicFillable
{
    protected static array $cachedFillableColumns = [];
    protected static array $dynamicRelations = [];
    protected static array $tableModelMapCache = [];
    protected array $excludeColumnTypes = [
        'json',
        'geometry',
    ];

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

                // Use the new helper to find the model class
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
                // Use the new helper here too
                $relatedModel = $instance->findModelClass($fk['table_name']);
                if (!$relatedModel) {
                    continue;
                }

                // ... (rest of the reverse relation logic is unchanged) ...
                $relationName = '';
                if ($fk['column_name'] === $standardFkName) {
                    $relationName = Str::camel(Str::plural($fk['table_name']));
                } else {
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
        // Check the static, in-memory cache
        return static::$tableModelMapCache[$tableName] ?? null;
    }

    /**
     * Override the model constructor.
     *
     * This runs on EVERY instantiation but is now extremely fast.
     */
    public function __construct(array $attributes = [])
    {
        // Only run our logic if $fillable hasn't been manually set
        if (empty($this->fillable)) {

            // Check that developer isn't "guarding all" (the default)
            // This check allows you to opt-out by just leaving $guarded = ['*']
            if ($this->guarded !== ['*']) {

                // Set fillable from our lightning-fast static cache
                $this->fillable = static::$cachedFillableColumns[static::class] ?? [];

                // IMPORTANT: Now that $fillable is set, we must
                // set $guarded to [] to "un-guard" the model.
                $this->guarded = [];
            }
        }

        parent::__construct($attributes);
    }
}
