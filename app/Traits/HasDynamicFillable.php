<?php

namespace App\Traits;

use App\Services\DatabaseSchemaCacheService;
use Illuminate\Database\Eloquent\Model;

trait HasDynamicFillable
{
    protected function initializeHasDynamicFillable(): void
    {
        if (!$this->getTable()) {
            throw new \RuntimeException('Model table name not defined.');
        }

        $schemaService = app(DatabaseSchemaCacheService::class);
        $columns = $schemaService->getColumns($this->getTable());
        $types = $schemaService->getColumnTypes($this->getTable());

        // Exclude non-fillable column types
        $nonFillableTypes = ['json', 'geometry'];
        $this->fillable = array_filter($columns, function ($column) use ($types, $nonFillableTypes) {
            return !in_array($types[$column] ?? '', $nonFillableTypes);
        });

        $this->fillable = array_diff($this->fillable, $this->getGuarded());
        if ($schemaService->isSoftDeletable($this->getTable())) {
            $this->fillable = array_diff($this->fillable, ['deleted_at']);
        }
    }
}
