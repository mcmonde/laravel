<?php

namespace App\Traits;

use App\Services\DatabaseSchemaCacheService;
use Illuminate\Support\Facades\Log;

trait HasDynamicFillable
{
    protected static array $cachedFillableColumns = [];

    protected array $excludeColumnTypes = [
        'json',
        'geometry',
    ];

    public static function bootHasDynamicFillable(): void
    {
        $class = static::class;

        if (isset(static::$cachedFillableColumns[$class])) {
            return; // Already booted for this class
        }

        try {
            $schema = app(DatabaseSchemaCacheService::class);
            $instance = new static; // Get an instance to read model properties
            $table = $instance->getTable();

            $allColumns = $schema->getColumns($table);
            $types = $schema->getColumnTypes($table);

            // 1. Get the model's defined $guarded list
            $guarded = $instance->getGuarded();

            // 2. Add soft-delete column to guarded list automatically
            if ($schema->isSoftDeletable($table)) {
                $guarded[] = 'deleted_at';
            }

            // 3. Get the types to exclude (from this trait or the model)
            $nonFillableTypes = $instance->excludeColumnTypes;

            // 4. Start with all columns and filter them down
            static::$cachedFillableColumns[$class] = collect($allColumns)
                // Filter out columns that are in the guarded list
                ->reject(fn ($column) => in_array($column, $guarded))
                // Filter out columns that match the non-fillable types
                ->reject(fn ($column) => in_array($types[$column] ?? '', $nonFillableTypes))
                ->values()
                ->all();

        } catch (\Throwable $e) {
            // Failsafe: This can happen if table doesn't exist yet
            Log::warning("Could not build dynamic fillable for $class: " . $e->getMessage());
            static::$cachedFillableColumns[$class] = [];
        }
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
