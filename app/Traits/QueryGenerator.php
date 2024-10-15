<?php

namespace App\Traits;

use Bouncer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

trait QueryGenerator
{
    public function index($request, $selected_relation_columns_only = [], $headers = []): array
    {
        $excludedIds = $request['excluded_id'] ?? null;

        $tableName = $this->model->getTable();
        $query = $this->model::query();

        // Get relations of the current table
        $foreignRelations = $this->getForeignTableRelations($tableName);
        $allTables = array_merge([['local' => null, 'table' => $tableName, 'foreign' => null, 'table_alias' => null]], $foreignRelations);

        // Generate selects based on foreign tables and selected columns
        $selects = $this->generateSelects($allTables, $selected_relation_columns_only);
        if (!empty($selects)) {
            $query->select($selects['selects']);
        }

        // Apply joins for foreign tables via separate function
        $this->applyJoins($query, $foreignRelations, $tableName);

        // Exclude specific IDs if provided
        if ($excludedIds) {
            $query->whereNotIn("{$tableName}.id", $excludedIds);
        }

        // Handle search filters
        $this->search($request, $query, $selects);
        $this->searchGlobal($request, $query, $selects);

        // Apply ordering
        foreach ($this->orderBy($request) as $order) {
            $query->orderBy($order['order_by'], $order['sort_order']);
        }

        // Get total count for pagination
        $total = $query->count();

        // Apply pagination logic
        $pagination = $this->paginate($request, $total);
        $list = $query->skip($pagination['skip'])->take($pagination['take'])->get();

        // Return if no results found
        if ($list->isEmpty()) {
            return [
                'message' => 'No results found.',
                'error' => null,
                'current_page' => null,
                'from' => null,
                'to' => null,
                'last_page' => null,
                'skip' => null,
                'take' => null,
                'total' => null,
                'headers' => $headers,
                'body' => null,
                'searchable' => $selects['columns'],
            ];
        }

        // Calculate last page
        $lastPage = ($pagination['take'] > 0) ? ceil($total / $pagination['take']) : 1;

        return [
            'message' => 'These are the results.',
            'error' => null,
            'current_page' => $pagination['current_page'],
            'from' => $pagination['skip'] + 1,
            'to' => min(($pagination['skip'] + $pagination['take']), $total),
            'last_page' => (int)$lastPage,
            'skip' => $pagination['skip'],
            'take' => $pagination['take'],
            'total' => $total,
            'headers' => $headers,
            'body' => $list,
            'searchable' => $selects['columns'],
        ];
    }

    public function create(): array
    {
        // TODO make database for the columns that can only be created.
        return ([
            'message' => 'Creating data.',
            'error' => null,
            'current_page' => null,
            'from' => null,
            'to' => null,
            'last_page' => null,
            'skip' => null,
            'take' => null,
            'total' => null,
            'headers' => null,
            'body' => [
                'columns' => $this->getTableColumnDetails($this->model->getTable()),
            ],
            'searchable' => null,
        ]);
    }

    public function store($request, $selected_relation_columns_only = [], $headers = []): array
    {
        DB::beginTransaction();
        try {
            $data = $this->model::create($request->validated());

            $model_name = $this->model->getTable();

            $result = $this->index(['search' => [['key' => "$model_name.id", 's' => $data['id'],]]], $selected_relation_columns_only, $headers);

            if ($result['body'])
                $result['message'] = 'Successfully created.';

            DB::commit();
            return $result;
        } catch (\Exception $exception) {
            DB::rollBack();
            // Please review the Logs if there are errors.
            return [
                'message' => 'An error occurred while storing.',
                'error' => $exception->getMessage(),
                'status' => 422
            ];
        }
    }

    public function show($id, $selected_relation_columns_only = []): array
    {
        $model_name = $this->model->getTable();

        $result = $this->index(['search' => [['key' => "$model_name.id", 's' => $id,]]], $selected_relation_columns_only);

        if ($result['body'])
            $result['message'] = 'Showing data.';

        return $result;
    }

    public function edit($id, $selected_relation_columns_only = []): array
    {
        $model_name = $this->model->getTable();
        $request = ['search' => [['key' => "$model_name.id", 's' => $id,]]];

        $result = $this->index($request, $selected_relation_columns_only);

        if ($result['body'])
            $result['message'] = 'Editing data.';

        return $result;
    }

    public function update($request, $id, $selected_relation_columns_only = [], $headers = []): array
    {
        $model_name = $this->model->getTable();
        $result = $this->index(['search' => [['key' => "$model_name.id", 's' => $id,]]], $selected_relation_columns_only, $headers);

        if ($result['body']) {
            DB::beginTransaction();
            try {
                $this->model::where('id', $id)->update($request->validated());

                $result = $this->index(['search' => [['key' => "$model_name.id", 's' => $id,]]]);
                $result['message'] = 'Successfully updated data.';
                DB::commit();
            } catch (\Exception $exception) {
                DB::rollBack();
                // Please review the Logs if there are errors.
                return [
                    'message' => 'An error occurred while updating.',
                    'error' => $exception->getMessage(),
                    'status' => 422
                ];
            }
        }

        return $result;
    }

    public function destroy($id, $selected_relation_columns_only = []): array
    {
        $data = $this->model::find($id);

        if (!$data) {
            return [
                'message' => 'No found data.',
                'status' => 404,
            ];
        }

        DB::beginTransaction();
        try {
            $model_name = $this->model->getTable();
            $request = ['search' => [['key' => "$model_name.id", 's' => $data['id'],]]];
            $data->delete();

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            // Please review the Logs if there are errors.
            return [
                'message' => 'An error occurred while deleting.',
                'error' => $exception->getMessage(),
                'status' => 422
            ];
        }

        return ([
            'message' => 'Successfully deleted data.',
            'error' => null,
            'current_page' => null,
            'from' => null,
            'to' => null,
            'last_page' => null,
            'skip' => null,
            'take' => null,
            'total' => null,
            'headers' => null,
            'body' => $this->index($request, $selected_relation_columns_only)['body'],
            'searchable' => null,
        ]);
    }

    public function forceDelete($id, $selected_relation_columns_only = []): array
    {
        $data = $this->model::when(in_array(SoftDeletes::class, class_uses($this->model)), function ($q) {
            $q->withTrashed();
        })
            ->find($id);

        if (!$data) {
            return [
                'message' => 'No found data.',
                'status' => 404,
            ];
        }

        $model_name = $this->model->getTable();
        $request = ['search' => [['key' => "$model_name.id", 's' => $data['id'],]]];

        // TODO add checking for relations before permanent deletion.
        DB::beginTransaction();
        try {
            $data->forceDelete();
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            // Please review the Logs if there are errors.
            return [
                'message' => 'An error occurred while invoking permanent deletion.',
                'error' => $exception->getMessage(),
                'status' => 422
            ];
        }

        return ([
            'message' => 'Permanently deleted the data.',
            'error' => null,
            'current_page' => null,
            'from' => null,
            'to' => null,
            'last_page' => null,
            'skip' => null,
            'take' => null,
            'total' => null,
            'headers' => null,
            'body' => $this->index($request, $selected_relation_columns_only)['body'],
            'searchable' => null,
        ]);
    }

    public function restore($id, $selected_relation_columns_only = []): array
    {
        $data = $this->model::find($id);

        if (!$data) {
            return [
                'message' => 'No found data.',
                'status' => 404,
            ];
        }

        $model_name = $this->model->getTable();
        $request = ['search' => [['key' => "$model_name.id", 's' => $data['id'],]]];

        DB::beginTransaction();
        try {
            $data->restore();
        } catch (\Exception $exception) {
            DB::rollBack();
            // Please review the Logs if there are errors.
            return [
                'message' => 'An error occurred while storing the purchase order.',
                'error' => $exception->getMessage(),
                'status' => 422
            ];
        }

        return ([
            'message' => 'Successfully restored data.',
            'error' => null,
            'current_page' => null,
            'from' => null,
            'to' => null,
            'last_page' => null,
            'skip' => null,
            'take' => null,
            'total' => null,
            'headers' => null,
            'body' => $this->index($request, $selected_relation_columns_only)['body'],
            'searchable' => null,
        ]);
    }

    // LIES CUSTOM QUERY GENERATORS HERE

    public function generateSelects(array $tables, $selected_columns = null): array
    {
        $selects = [];
        $new_column_names = [];
        $columnInfo = [];

        // Fetch column information from cache or database
        foreach ($tables as $table) {
            $tableName = $table['table_alias'] ??  $table['table'];
            $cacheKey = 'table_columns_' . $tableName;

            // Check if column information is cached
            if (Cache::has($cacheKey)) {
                $columnInfo[$tableName] = Cache::get($cacheKey);
            } else {
                // If not cached, fetch from Schema and store in cache
                $columnInfo[$tableName] = Schema::getColumnListing($table['table']);
                Cache::forever($cacheKey, $columnInfo[$tableName]);
            }
        }

        // Fetch and cache column types for each table
        foreach ($tables as $table) {
            $tableName = $table['table_alias'] ??  $table['table'];
            $cacheKey = 'table_column_types_' . $tableName;

            // Check if column types are cached
            if (!Cache::has($cacheKey)) {
                $columnTypes = [];
                foreach ($columnInfo[$tableName] as $column) {
                    //                    if (!in_array($column, ['created_at', 'updated_at', 'deleted_at'])) {
                    $columnTypes[$column] = Schema::getColumnType($table['table'], $column);
                    //                    }
                }
                Cache::forever($cacheKey, $columnTypes);
            }
        }

        // Process selected columns
        if ($selected_columns) {
            foreach ($tables as &$table) {
                $tableName = $table['table_alias'] ?? $table['table'];
                if (isset($selected_columns[$tableName])) {
                    $table['columns'] = $selected_columns[$tableName];
                }
            }
            unset($table);
        }

        // Build selects and new column names
        foreach ($tables as $table) {
            $tableName = $table['table_alias'] ?? $table['table'];
            $columns = $table['columns'] ?? $columnInfo[$tableName];

            foreach ($columns as $column) {
                //                if (!in_array($column,['created_at','updated_at','deleted_at'])) {
                $columnType = Cache::get('table_column_types_' . $tableName)[$column];
                $newColumnName = $tableName . '_' . $column;
                $new_column_names[] = [
                    'column' => $tableName . '.' . $column,
                    'name' => $newColumnName,
                    'type' => $columnType
                ];
                $selects[] = $tableName . '.' . $column . ' as ' . $newColumnName;
                //                }
            }
        }

        return [
            'selects' => $selects,
            'columns' => $new_column_names,
        ];
    }

    public function getColumns($table = null): array
    {
        if (!null)
            return Schema::getColumnListing($table);

        return [];
    }

    public function getForeignTableRelations($table, $has_many = null): array
    {
        // TODO add that handles table to many relations.

        $foreign_tables = Schema::getForeignKeys($table);

        $foreign = [];
        foreach ($foreign_tables as $foreign_table) {
            $foreign[] = [
                'local' => $foreign_table['columns'][0],
                'table' => $foreign_table['foreign_table'],
                'foreign' => $foreign_table['foreign_columns'][0],
                'table_alias' => $foreign_table['name']
            ];
        }

        return $foreign;
    }

    public function getTableColumnDetails($table = null, $excluded = ['id', 'created_at', 'updated_at', 'deleted_at']): array
    {
        $columns = Schema::getColumns($table);

        $foreign_keys = Schema::getForeignKeys($table);

        $details = [];

        foreach ($columns as $column) {
            if (!in_array($column['name'], $excluded)) {
                $is_foreign_key = false;
                $foreign_table = [];
                $foreign_table_column = [];

                foreach ($foreign_keys as $foreign_key) {
                    if (in_array($column['name'], $foreign_key['columns'], true)) {
                        $is_foreign_key = true;
                        $foreign_table = $foreign_key['foreign_table'];
                        $foreign_table_column = $foreign_key['foreign_columns'][0];
                        break;
                    }
                }

                $details[] = [
                    'column' => $column['name'],
                    'type' => $column['type_name'],
                    'type_details' => $column['type'],
                    'nullable' => $column['nullable'],
                    'foreign_key' => $is_foreign_key ? [
                        'relation' => $foreign_table,
                        'key' => $foreign_table_column,
                        'url' => '/' . str_replace('_', '-', $foreign_table)
                    ] : null,
                ];
            }
        }

        return $details;
    }

    protected function loadRelationships(Builder $query, array $relationships): void
    {
        foreach ($relationships as $relationship) {
            if (!$this->isValidRelationship($query, $relationship)) {
                throw new \InvalidArgumentException("Invalid relationship: $relationship");
            }
            $query->with($relationship);
        }
    }

    protected function isValidRelationship(Builder $query, $relationship): bool
    {
        // Use Eloquent's method to check if the relationship is valid
        return method_exists($query->getModel(), $relationship);
    }

    protected function applyJoins($query, $foreignRelations, $tableName): void
    {
        foreach ($foreignRelations as $relation) {
            $alias = $relation['table_alias'] ?? '';
            $joinTable = $relation['table'];
            $joinLocal = $relation['local'];
            $joinForeign = $relation['foreign'];

            $query->leftJoin(
                $alias ? "{$joinTable} as {$alias}" : $joinTable,
                function ($join) use ($tableName, $joinLocal, $alias, $joinTable, $joinForeign) {
                    $join->on("{$tableName}.{$joinLocal}", '=', ($alias ?: $joinTable) . ".{$joinForeign}")
                        ->whereNull(($alias ?: $joinTable) . '.deleted_at');
                }
            );
        }
    }

    public function search($request, $query, $selects): void
    {
        if (isset($request['search'])) {
            if (is_array($request['search'])) {
                foreach ($request['search'] as $search) {
                    $s = trim($search['s'] ?? '');
                    $key_index = array_search($search['key'], array_column($selects['columns'], 'column'));

                    if ($s != "" && $key_index !== false) {
                        if (is_numeric($s))
                            $query->where($search['key'], '=', $s);
                        else
                            $query->where($search['key'], 'ilike', "%" . $s . "%");

                        unset($selects['columns'][$key_index]);
                    }
                }
            }
        }

        if (!empty($request['date_column'])) {
            $key_index = array_search($request['date_column'], array_column($selects['columns'], 'column'));
            if ($key_index !== false) {
                if ($selects['columns'][$key_index]['type'] == 'timestamp') {
                    if (!empty($request['date_from'])) {
                        if (!empty($request['date_to'])) {
                            $query->whereBetween($request['date_column'], [$request['date_from'], $request['date_to']]);
                        } else {
                            $query->whereBetween($request['date_column'], [$request['date_from'], $request['date_from']]);
                        }
                    }
                }
            }
        }
    }

    public function searchGlobal($request, $query, $selects, $where = true): void
    {
        if (isset($request['search_global']) && $request['search_global']) {
            //            $dateTimeObject = \DateTime::createFromFormat('Y-m-d H:i:s', $request['search_global']);
            //            if ( ctype_digit($request['search_global']) || is_bool($request['search_global']) ||
            //                ($dateTimeObject !== false && is_a($dateTimeObject, \DateTime::class))) {
            //                $operator = '=';
            //                $search = $request['search_global'];
            //            } else {
            //                $operator = 'ilike';
            //                $search = "%".$request['search_global']."%";
            //            }

            if ($where) {
                $query->where(function ($query) use ($selects, $request) {
                    $this->generator($query, $selects, $request);
                });
            } else {
                $query->orWhere(function ($query) use ($selects, $request) {
                    $this->generator($query, $selects, $request);
                });
            }
        }
    }

    public function havingSearch($request, $query, $selects): void
    {
        if (isset($request['search'])) {
            if (is_array($request['search'])) {
                foreach ($request['search'] as $search) {
                    $s = trim($search['s']);
                    $key_index = array_search($search['key'], array_column($selects['columns'], 'column'));

                    if ($s != "" && $key_index !== false) {
                        if (is_numeric($s))
                            $query->orHaving($search['key'], '=', $s);
                        else
                            $query->orHaving($search['key'], 'ilike', "%" . $s . "%");

                        unset($selects['columns'][$key_index]);
                    }
                }
            }
        }

        if (!empty($request['date_column'])) {
            $key_index = array_search($request['date_column'], array_column($selects['columns'], 'column'));
            if ($key_index !== false) {
                if ($selects['columns'][$key_index]['type'] == 'timestamp') {
                    if (!empty($request['date_from'])) {
                        if (!empty($request['date_to'])) {
                            $query->havingBetween($request['date_column'], [$request['date_from'], $request['date_to']]);
                        } else {
                            $query->havingBetween($request['date_column'], [$request['date_from'], $request['date_from']]);
                        }
                    }
                }
            }
        }
    }

    public function havingSearchGlobal($request, $query, $selects, $where = true): void
    {
        if (isset($request['search_global']) && $request['search_global']) {
            //            $dateTimeObject = \DateTime::createFromFormat('Y-m-d H:i:s', $request['search_global']);
            //            if ( ctype_digit($request['search_global']) || is_bool($request['search_global']) ||
            //                ($dateTimeObject !== false && is_a($dateTimeObject, \DateTime::class))) {
            //                $operator = '=';
            //                $search = $request['search_global'];
            //            } else {
            //                $operator = 'ilike';
            //                $search = "%".$request['search_global']."%";
            //            }

            if ($where) {
                $this->generator($query, $selects, $request, 'having');
            } else {
                $this->generator($query, $selects, $request, 'orHaving');
            }
        }
    }

    public function generator($query, $selects, $request, $condition = 'orWhere'): void
    {
        $search_global = $request['search_global'] ?? null;

        foreach ($selects['columns'] as $column) {
            $type = $column['type'];
            if ($type == 'timestamp') {
                continue;
                // TODO improve this datetime searching.

                //                        $query->$condition(function ($query) use ($request, $column) {
                //                            $query->whereDate($column['column'], '=', date('Y-m-d', strtotime($search_global)))
                //                                ->whereTime($column['column'], '>=', date('H:i:s', strtotime($search_global)))
                //                                ->$condition(function ($query) use ($request, $column) {
                //                                    $query->whereDate($column['column'], '<>', date('Y-m-d', strtotime($search_global)))
                //                                        ->whereTime($column['column'], '<', date('H:i:s', strtotime($search_global)));
                //                                });
                //                        });
            } elseif ($type == 'int8') {
                if (ctype_digit($search_global))
                    $query->$condition($column['column'], '=', $search_global);
            } elseif ($type == 'int4') {
                if (ctype_digit($search_global) && $search_global >= -2147483648 && $search_global <= 2147483647) {
                    $query->$condition($column['column'], '=', $search_global);
                }
            } elseif ($type == 'int2') {
                if (ctype_digit($search_global) && $search_global >= -32768 && $search_global <= 32767) {
                    $query->$condition($column['column'], '=', $search_global);
                }
            } elseif ($type == 'bool') {
                if (is_bool($search_global)) {
                    $query->$condition($column['column'], $search_global);
                } elseif (strtolower($search_global) == 'true' || strtolower($search_global) == 'false') {
                    $query->$condition($column['column'], strtolower($search_global) == 'true' ? 1 : 0);
                }
            } elseif ($type == 'float8') {
                if (is_numeric($search_global)) {
                    $query->$condition($column['column'], '=', $search_global);
                }
            } else {
                $query->$condition($column['column'], 'ilike', "%" . $search_global . "%");
            }
        }

        //        List of column types in PostgreSQL
        //
        //        int2 (smallint):      2-byte signed integer
        //        int4 (integer):       4-byte signed integer
        //        int8 (bigint):        8-byte signed integer
        //        serial:               Autoincrementing integer starting from 1
        //        serial2:              Autoincrementing smallint starting from 1
        //        serial4:              Autoincrementing integer starting from 1
        //        serial8:              Autoincrementing bigint starting from 1
        //        numeric:              Arbitrary precision numeric type
        //        float4 (real):        4-byte floating point number
        //        float8 (double precision): 8-byte floating point number
        //        varchar(n):           Variable-length character string with a maximum length of n
        //        text:                 Variable-length character string (unlimited length)
        //        char(n):              Fixed-length character string of length n
        //        date:                 Date (without time)
        //        time:                 Time (without date)
        //        timestamp:            Date and time
        //        timestamptz (timestamp with time zone): Date and time with time zone
        //        bool (boolean):       True or false
        //        json:                 JSON data
        //        jsonb:                Binary JSON data
        //        bytea:                Binary data (byte array)
        //        interval:             Time interval
        //        uuid:                 Universally unique identifier
        //        money:                Currency amount
        //        xml:                  XML data
        //        oid:                  Object identifier
        //        tsvector:             Full-text search vector
        //        tsquery:              Full-text search query
        //        macaddr:              MAC (Media Access Control) address
        //        inet:                 IP address or range of IP addresses
        //        cidr:                 IPv4 or IPv6 network address
        //        bit(n):               Fixed-length bit string of length n
        //        bit varying(n):       Variable-length bit string of maximum length n
        //        box:                  Rectangular box on a plane
        //        circle:               Circle on a plane
        //        line:                 Infinite line
        //        lseg:                 Line segment
        //        path:                 Closed geometric path
        //        point:                Geometric point
        //        polygon:              Closed geometric polygon

    }

    public function orderBy($request): array
    {
        $order_by = [];
        if (isset($request['list_orders']))
            foreach ($request['list_orders'] as $order) {
                if (isset($order['order_by']) && isset($order['sort_order'])) {
                    if ($order['order_by'] != null && $order['order_by'] != "" &&  (strtolower(trim($order['sort_order'])) == 'asc' || strtolower(trim($order['sort_order'])) == 'desc')) {
                        $order_by[] = [
                            'order_by' => $order['order_by'],
                            'sort_order' => $order['sort_order']
                        ];
                    }
                }
            }

        return $order_by;
    }

    public function paginate($request, $total): array
    {
        $current_page = isset($request['page']) ? (is_numeric($request['page']) && $request['page'] > 0) ? $request['page'] : 1 : 1;
        $take = isset($request['show']) ? (is_numeric($request['show']) ? $request['show'] : (($request['show'] == 'all') ? $this->model->count() : $total)) : 15;
        $skip = (is_numeric($take) ? $take : 0) * ((isset($request['page']) && $request['page'] > 1) ? ($request['page'] - 1) : 0);

        return [
            'current_page' => $current_page,
            'take' =>  $take,
            'skip' => $skip
        ];
    }

    public function getJsonResponse(array $data): JsonResponse
    {
        $status = $data['status'] ?? 200;
        return response()->json([
            'message' => $data['message'] ?? null,
            'error' => $data['error'] ?? null,
            'details' => [
                'current_page' => $data['current_page'] ?? null,
                'from' => isset($data['skip']) ? $data['skip'] + 1 : null,
                'to' => $data['to'] ?? null,
                'last_page' => $data['last_page'] ?? null,
                'skip' => $data['skip'] ?? null,
                'take' => $data['take'] ?? null,
                'total' => $data['total'] ?? null,
            ],
            'headers' => $data['headers'] ?? null,
            'body' => $data['body'] ?? null,
            'searchable' => $data['searchable'] ?? null,
            'others' => $data['others'] ?? null,
        ], $status);
    }

    public function getRelatedTables(string $tableName): array
    {
        // Define a unique cache key based on the table name
        $cacheKey = "related_tables_{$tableName}";

        // Fetch related tables from cache or execute the query if not found
        return Cache::rememberForever($cacheKey, function () use ($tableName) {
            return DB::table('information_schema.table_constraints as tc')
                ->join('information_schema.key_column_usage as kcu', function ($join) {
                    $join->on('tc.constraint_name', '=', 'kcu.constraint_name')
                        ->on('tc.table_schema', '=', 'kcu.table_schema');
                })
                ->join('information_schema.constraint_column_usage as ccu', function ($join) {
                    $join->on('ccu.constraint_name', '=', 'tc.constraint_name')
                        ->on('ccu.table_schema', '=', 'tc.table_schema');
                })
                ->where('tc.constraint_type', 'FOREIGN KEY')
                ->where('ccu.table_name', $tableName)
                ->where('ccu.column_name', 'id')
                ->select([
                    'tc.table_name',
                    'kcu.column_name',
                    'tc.constraint_name',
                    'ccu.table_name as foreign_table_name',
                    'ccu.column_name as foreign_column_name',
                ])
                ->get()
                ->toArray();
        });
    }


    public function isDeleteable($table_name, $id)
    {
        $relatedTables = $this->getRelatedTables($table_name);
        foreach ($relatedTables as $relatedTable) {
            $relatedModelClass = "App\\Models\\" . ucfirst(Str::singular(Str::camel($relatedTable->table_name)));
            if (class_exists($relatedModelClass)) {
                $relatedModel = new $relatedModelClass;
                $exist = $relatedModel->where($relatedTable->column_name, $id)->whereNull('deleted_at')->exists();
                if ($exist) return $exist;
                else continue;
            }
        }
        return false;
    }

    public function deleteRelatedEntries(string $table_name, int $id): void
    {
        $relatedTables = $this->getRelatedTables($table_name);

        foreach ($relatedTables as $relatedTable) {
            $relatedModelClass = "App\\Models\\" . ucfirst(Str::singular(Str::camel($relatedTable->table_name)));

            // Check if the related model exists
            if (class_exists($relatedModelClass)) {
                $relatedModel = new $relatedModelClass;

                // Get related records
                $relatedRecords = $relatedModel->where($relatedTable->column_name, $id)
                    ->whereNull('deleted_at')
                    ->get();

                // Perform cascading soft delete on each related record
                if ($relatedRecords->isNotEmpty()) {
                    $relatedRecordIds = $relatedRecords->pluck('id');

                    // Recursively delete related entries
                    foreach ($relatedRecordIds as $relatedRecordId) {
                        $this->deleteRelatedEntries($relatedTable->table_name, $relatedRecordId);
                    }

                    // Perform soft delete on the batch of related records
                    if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($relatedModel))) {
                        // Soft delete related entries
                        $relatedModel->whereIn('id', $relatedRecordIds)->delete();
                    } else if (Schema::hasColumn($relatedTable->table_name, 'deleted_at')) {
                        // Manually update the deleted_at column
                        $relatedModel->whereIn('id', $relatedRecordIds)->update(['deleted_at' => now()]);
                    } else {
                        // Perform direct deletion
                        $relatedModel->whereIn('id', $relatedRecordIds)->delete();
                    }
                }
            }
        }
    }
}
