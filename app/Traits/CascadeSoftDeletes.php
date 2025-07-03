<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

trait CascadeSoftDeletes
{
    public static function bootCascadeSoftDeletes(): void
    {
        static::deleting(function ($model) {
            $model->cascadeDeleteRelatedRecords();
            // $model->cascadeDeletePivotRecords();
        });
    }

    protected function cascadeDeleteRelatedRecords(): void
    {
        DB::beginTransaction();
        try {
            $relationshipMap = $this->getRelationshipMap();
            $softDeletableTables = $this->getSoftDeletableTables();

            $pendingDeletes = [$this->getTable() => [$this->getKey()]];

            while (!empty($pendingDeletes)) {
                $newPendingDeletes = [];

                foreach ($pendingDeletes as $table => $ids) {
                    if (empty($ids)) continue;

                    $childTables = $relationshipMap[$table] ?? [];

                    foreach ($childTables as $childTable => $foreignKey) {
                        if (!isset($softDeletableTables[$childTable])) continue;

                        $relatedIds = [];

                        // if ($childTable === 'users' && $foreignKey === 'created_by') {
                        //     DB::table('users')
                        //         ->whereIn('created_by', $ids)
                        //         ->update(['created_by' => null]);
                        //     continue;
                        // }

                        // Batch collect related IDs
                        DB::table($childTable)
                            ->whereIn($foreignKey, $ids)
                            ->whereNull('deleted_at')
                            ->chunkById(1000, function ($rows) use (&$newPendingDeletes, $childTable) {
                                foreach ($rows as $row) {
                                    $newPendingDeletes[$childTable][] = $row->id;
                                }
                            });

                        if (!empty($newPendingDeletes[$childTable])) {
                            DB::table($childTable)
                                ->whereIn('id', $newPendingDeletes[$childTable])
                                ->update(['deleted_at' => now()]);
                        }
                    }
                }

                $pendingDeletes = $newPendingDeletes;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function getRelationshipMap(): array
    {
        return Cache::remember('cascade_relationship_map', 3600, function () {
            $relationships = DB::select("
                SELECT
                    ccu.table_name as parent_table,
                    tc.table_name as child_table,
                    kcu.column_name as foreign_key
                FROM
                    information_schema.table_constraints AS tc
                    JOIN information_schema.key_column_usage AS kcu
                        ON tc.constraint_name = kcu.constraint_name
                        AND tc.table_schema = kcu.table_schema
                    JOIN information_schema.constraint_column_usage AS ccu
                        ON ccu.constraint_name = tc.constraint_name
                        AND ccu.table_schema = tc.table_schema
                WHERE tc.constraint_type = 'FOREIGN KEY'
                    AND tc.table_schema = current_schema()
            ");

            $map = [];
            foreach ($relationships as $rel) {
                if (!isset($map[$rel->parent_table])) {
                    $map[$rel->parent_table] = [];
                }
                $map[$rel->parent_table][$rel->child_table] = $rel->foreign_key;
            }
            return $map;
        });
    }

    protected function getSoftDeletableTables(): array
    {
        return Cache::remember('soft_deletable_tables', 3600, function () {
            return array_column(DB::select("
                SELECT DISTINCT tablename FROM pg_catalog.pg_tables
                WHERE schemaname = current_schema() AND EXISTS (
                    SELECT 1 FROM information_schema.columns
                    WHERE table_name = tablename AND column_name = 'deleted_at'
                )
            "), 'tablename', 'tablename');
        });
    }

    // protected function cascadeDeletePivotRecords()
    // {
    //     $softDeletableTables = $this->getSoftDeletableTables();

    //     foreach ($this->getPivotTables() as $pivotTable => $foreignKey) {
    //         if (isset($softDeletableTables[$pivotTable])) {
    //             DB::table($pivotTable)
    //                 ->where($foreignKey, $this->getKey())
    //                 ->whereNull('deleted_at')
    //                 ->update(['deleted_at' => now()]);
    //         }
    //     }
    // }

    // protected function getPivotTables()
    // {
    //     return ['assigned_roles' => 'entity_id'];
    // }
}
