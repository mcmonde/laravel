<?php

namespace App\Repositories;

use App\Models\Ability;
use App\Models\Role;
use App\Traits\QueryGenerator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Bouncer;

class RoleRepository
{
    use QueryGenerator;

    protected Role $model;

    public function __construct(Role $model)
    {
        $this->model = $model;
    }

    public function index($payload, $selected_relation_columns_only = [], $headers = []): array
    {
        $excludedIds = $payload['excluded_id'] ?? null;

        // exclude super-admin in the option if not super-admin user.
        $roles = auth()->user()->roles;
        if (!in_array('super-admin', $roles->pluck('name')->toArray())) {
            $id = $this->model::where('name', 'super-admin')->pluck('id')->first();
            $excluded_ids = array_merge($excludedIds, array($id));
        }

        $tableName = $this->model->getTable();
        $query = $this->model::query();

        $total = $query->count();

        // Get relations of the current table
        $foreignRelations = $this->getForeignTableRelations($tableName);
        $allTables = array_merge([['local' => null, 'table' => $tableName, 'foreign' => null, 'table_alias' => null]], $foreignRelations);

        // Generate selects based on foreign tables and selected columns
        $selects = $this->generateSelects($allTables, $selected_relation_columns_only);
        if (!empty($selects)) {
            $query->select($selects['selects']);
        }

        $query->addSelect(DB::raw('COUNT(assigned_roles.entity_id) as user_counts'));

        // Apply joins for foreign tables via separate function
        $this->applyLeftJoins($query, $foreignRelations, $tableName);

        $query->leftJoin(DB::raw("(SELECT * FROM assigned_roles WHERE entity_type = 'App\Models\User') AS assigned_roles"), function ($join) {
            $join->on('assigned_roles.role_id', '=', 'roles.id')
                ->whereNull('assigned_roles.deleted_at');
        });

        // Exclude specific IDs if provided
        if ($excludedIds) {
            $query->whereNotIn("{$tableName}.id", $excludedIds);
        }

        // SEARCHING COLUMNS OR UNIQUE COLUMNS HERE
        $this->search($payload, $query, $selects);
        $this->searchGlobal($payload, $query, $selects);

        $query->groupBy('roles.id');

        // SCOPE SHOULD BE ADDED HERE.

        foreach ($this->orderBy($payload) as $order)
            $query->orderBy($order['order_by'], $order['sort_order']);

        // UNIQUE ELOQUENCE SHOULD BE ADDED HERE.

        // Apply pagination logic
        $pagination = $this->paginate($payload, $total);
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

    public function store($payload, $selected_relation_columns_only = [], $headers = []): array
    {
        $data = Bouncer::role()->create($payload);
        $data->abilities()->sync($payload['permissions']);

        $model_name = $this->model->getTable();
        $payload = ['search' => [['key' => "{$model_name}.id", 's' => $data['id'],]]];

        $result = $this->index($payload);

        if ($result['body'])
            $result['message'] = 'Successfully created.';

        return $result;
    }

    public function edit($id, $selected_relation_columns_only = []): array
    {
        $data = $this->model::find($id);

        if (!$data) {
            return [
                'message' => 'No found data.',
                'status' => 404,
            ];
        }

        $model_name = $this->model->getTable();
        $payload = ['search' => [['key' => "{$model_name}.id", 's' => $id,]]];

        $payload['abilities'] = (new AbilityRepository(new Ability()))->getDataAbilities($data);

        $result =  $this->index($payload);

        if ($result['body'])
            $result['others'] = ['abilities' => (new AbilityRepository(new Ability()))->getDataAbilities($data)];

        return $result;
    }

    public function update($payload, $id, $selected_relation_columns_only = [], $headers = []): array
    {

        $model_name = $this->model->getTable();
        $result = $this->index(['search' => [['key' => "{$model_name}.id", 's' => $id,]]], $selected_relation_columns_only, $headers);

        if ($result['body']) {
            $data = $this->model::where('id', $id)->first();
            $data->update(['title' => $payload['title'],]);

            $permissions = $payload['permissions'] ?? null;

            if ($permissions)
                $data->abilities()->sync($permissions);

            $result = $this->index(['search' => [['key' => "{$model_name}.id", 's' => $id,]]]);
            $result['message'] = 'Successfully updated data.';
        }

        return $result;
    }
}
