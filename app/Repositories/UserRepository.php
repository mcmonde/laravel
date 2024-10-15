<?php

namespace App\Repositories;

use App\Models\Role;
use App\Models\User;
use App\Traits\QueryGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Bouncer;

class UserRepository
{
    use QueryGenerator;

    protected User $model;

    public function __construct(User $model)
    {
        $this->model = $model;
    }

    public function index($payload, $selected_relation_columns_only = [], $headers = []): array
    {
        $excludedIds = $payload['excluded_id'] ?? null;

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

        $query->addSelect(DB::raw("COALESCE(users_created_by_foreign.first_name ||' ', '')
                            || COALESCE(users_created_by_foreign.middle_name ||' ', '')
                            || COALESCE(users_created_by_foreign.last_name, '')
                            AS created_by_full_name"));

        $query->addSelect(DB::raw('STRING_AGG(DISTINCT CAST(roles.name AS VARCHAR), \', \' ) as roles'));
        $query->addSelect(DB::raw('STRING_AGG(DISTINCT CAST(roles.id AS VARCHAR), \', \' ) as roles_ids'));
        $query->addSelect(DB::raw("COALESCE(users.first_name ||' ', '')
                            || COALESCE(users.middle_name ||' ', '')
                            || COALESCE(users.last_name, '')
                            AS full_name"));

        // Apply joins for foreign tables via separate function
        $this->applyLeftJoins($query, $foreignRelations, $tableName);

        $query->leftJoin('assigned_roles', function ($join) {
            $join->on('assigned_roles.entity_id', '=', 'users.id')
                ->whereNull('assigned_roles.deleted_at');
        });
        $query->leftJoin('roles', function ($join) {
            $join->on('roles.id', '=', 'assigned_roles.role_id')
                ->whereNull('roles.deleted_at');
        });

        // Exclude specific IDs if provided
        if ($excludedIds) {
            $query->whereNotIn("{$tableName}.id", $excludedIds);
        }

        $query->where('assigned_roles.entity_type', '=', 'App\\Models\\User');

        if (isset($payload['search_global']))
            $role_ids = Role::where('name', 'ILIKE', "%{$payload['search_global']}%")->pluck('id')->toArray();
        else
            $role_ids = null;

        $where = true;
        if (!empty($role_ids)) {
            $where = false;
            $query->whereIn('assigned_roles.role_id', $role_ids);
        }

        $query->whereNull('users.deleted_at');

        // SEARCHING COLUMNS OR UNIQUE COLUMNS HERE
        $this->search($payload, $query, $selects);
        $this->searchGlobal($payload, $query, $selects, $where);

        $query->groupBy('users.id');
        $query->groupBy('users_created_by_foreign.id');

        // SCOPE SHOULD BE ADDED HERE.

        // Apply ordering
        foreach ($this->orderBy($payload) as $order) {
            $query->orderBy($order['order_by'], $order['sort_order']);
        }

        // UNIQUE ELOQUENCE SHOULD BE ADDED HERE.

        // Perform a subquery to count individual rows
        $subQuery = clone $query;
        $subQuery->select(DB::raw('COUNT(*) as count'))->getQuery();

        // Use the subquery to count rows
        $total = DB::table(DB::raw("({$subQuery->toSql()}) as sub"))
            ->mergeBindings($subQuery->getQuery())
            ->count();

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
        $roles = Role::whereIn('id', $payload['role_id']);

        $data = $this->model::create([
            'first_name' => $payload['first_name'],
            'middle_name' => $payload['middle_name'],
            'last_name' => $payload['last_name'],
            'email' => $payload['email'],
            'password' => Hash::make($payload['password']),
            'address' => $payload['address'],
            'contact_number' => $payload['contact_number'],
            'status' => $payload['status'],
            'tin' => $payload['tin'],
            'allow_login' => $payload['allow_login'],
            'created_by' => $payload['created_by'],
        ]);

        Bouncer::assign($roles->pluck('name')->toArray())->to($data);

        $model_name = $this->model->getTable();

        $result = $this->index(['search' => [['key' => "{$model_name}.id", 's' => $data['id'],]]]);

        if ($result['body'])
            $result['message'] = 'Successfully created.';

        return $result;
    }

    public function show($id, $selected_relation_columns_only = [], $headers = []): array
    {
        $user = auth()->user();

        $model_name = $this->model->getTable();

        $result = $this->index(['search' => [['key' => "{$model_name}.id", 's' => $id,]]], $selected_relation_columns_only, $headers);

        if (!Bouncer::is($user)->a('super-admin') && $id != $user['id'])
            $result = $this->index(['search' => [['key' => "{$model_name}.id", 's' => 0,]]]);

        if ($result['body'])
            $result['message'] = 'Showing data.';

        return $result;
    }

    public function edit($id, $selected_relation_columns_only = []): array
    {
        $user = auth()->user();

        $model_name = $this->model->getTable();

        $result = $this->index(['search' => [['key' => "{$model_name}.id", 's' => $id,]]]);

        if (!Bouncer::is($user)->a('super-admin'))
            $result = $this->index(['search' => [['key' => "{$model_name}.id", 's' => 0,]]]);

        if ($result['body'])
            $result['message'] = 'Showing data.';

        return $result;
    }

    public function update($payload, $id, $selected_relation_columns_only = [], $headers = []): array
    {
        $new_roles = Role::whereIn('id', $payload['role_id'])->get();

        $user = auth()->user();
        $roles = $user->roles->toArray();

        $is_super_admin = array_filter($roles, function ($role) {
            return $role['name'] == 'super-admin';
        });

        $query = $this->model::query();
        $model_name = $this->model->getTable();

        $result = $this->index(['search' => [['key' => "{$model_name}.id", 's' => $id,]]], $selected_relation_columns_only);

        if (!$is_super_admin && $id != $user['id']) {
            $result = $this->index(['search' => [['key' => "{$model_name}.id", 's' => 0,]]], $selected_relation_columns_only);
        }

        $data = $query->where('id', $id)->first();
        $current_password = $payload['current_password'] ?? null;
        $new_password = $payload['new_password'] ?? null;

        if ($result['body']) {
            if ($is_super_admin) {
                // super-admin does not need current password.
                if ($new_password) {
                    $data->update([
                        'first_name' =>     $payload['first_name'],
                        'middle_name' =>    $payload['middle_name'],
                        'last_name' =>      $payload['last_name'],
                        'email' =>          $payload['email'],
                        'address' =>        $payload['address'],
                        'contact_number' => $payload['contact_number'],
                        'status' =>         $payload['status'],
                        'allow_login' =>    $payload['allow_login'],
                        'tin' =>            $payload['tin'],
                        'password' =>       bcrypt($new_password)
                    ]);
                } else {
                    $data->update([
                        'first_name' =>     $payload['first_name'],
                        'middle_name' =>    $payload['middle_name'],
                        'last_name' =>      $payload['last_name'],
                        'email' =>          $payload['email'],
                        'address' =>        $payload['address'],
                        'contact_number' => $payload['contact_number'],
                        'status' =>         $payload['status'],
                        'tin' =>            $payload['tin'],
                        'allow_login' =>    $payload['allow_login'],
                    ]);
                }

                Bouncer::sync($data)->roles($new_roles);
                $result = $this->index(['search' => [['key' => "{$model_name}.id", 's' => $id,]]], $selected_relation_columns_only);
            } elseif ($current_password) {
                // other roles should verify their current password.
                if (Hash::check($current_password, $data->password)) {
                    if ($new_password) {
                        if ($new_password == $current_password) {
                            $result = $this->index(['search' => [['key' => "{$model_name}.id", 's' => $id,]]], $selected_relation_columns_only);
                            $result['message'] = 'Your current and new password are the same. Please provide a new password.';
                            $result['error'] = [
                                'current_password' => ['Your current and new password are the same. Please provide a new password.'],
                                'new_password' => ['Your current and new password are the same. Please provide a new password.'],
                            ];
                            $result['status'] = 401;
                        } else {
                            $data->update([
                                'first_name' =>     $payload['first_name'],
                                'middle_name' =>    $payload['middle_name'],
                                'last_name' =>      $payload['last_name'],
                                'email' =>          $payload['email'],
                                'address' =>        $payload['address'],
                                'contact_number' => $payload['contact_number'],
                                'status' =>         $payload['status'],
                                'allow_login' =>    $payload['allow_login'],
                                'tin' =>            $payload['tin'],
                                'password' =>       bcrypt($new_password)
                            ]);
                            $result = $this->index(['search' => [['key' => "{$model_name}.id", 's' => $id,]]], $selected_relation_columns_only);
                        }
                    } else {
                        $data->update([
                            'first_name' =>     $payload['first_name'],
                            'middle_name' =>    $payload['middle_name'],
                            'last_name' =>      $payload['last_name'],
                            'email' =>          $payload['email'],
                            'address' =>        $payload['address'],
                            'contact_number' => $payload['contact_number'],
                            'status' =>         $payload['status'],
                            'tin' =>            $payload['tin'],
                            'allow_login' =>    $payload['allow_login'],
                        ]);
                        $result = $this->index(['search' => [['key' => "{$model_name}.id", 's' => $id,]]], $selected_relation_columns_only);
                    }
                } else {
                    $result = $this->index(['search' => [['key' => "{$model_name}.id", 's' => $id,]]], $selected_relation_columns_only);
                    $result['message'] = 'Incorrect password.';
                    $result['error'] = ['current_password' => ['Incorrect password.']];
                    $result['status'] = 401;
                }
            } else {
                $result = $this->index(['search' => [['key' => "{$model_name}.id", 's' => $id,]]], $selected_relation_columns_only);
                $result['message'] = 'Please provide your current password for verification.';
                $result['error'] = ['current_password' => ['Please provide your current password for verification.']];
                $result['status'] = 401;
            }
        }

        return $result;
    }
}
