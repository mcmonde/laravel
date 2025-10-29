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

trait HasRepositoryCrud
{
    public function index($payload): array
    {
        $search = $payload['search_global'] ?? null;
        $excludedIds = $payload['excluded_id'] ?? null;
        $includedIds = $payload['included_id'] ?? null;

        $filters = [];
        $sort = [];


        $tableName = $this->model->getTable();
        $ability = Str::plural(str_replace('_', '-', Str::snake($tableName)));


        if ($excludedIds) {
            $filters[] = 'relation_id NOT IN ['.implode(', ',$excludedIds).']';
        }
        if ($includedIds) {
            $filters[] = 'relation_id IN ['.implode(', ', $includedIds).']';
        }
        // ADD MORE CONDITIONS FOR FILTERS HERE...

        $filterString = implode(' AND ', $filters);

        if (!$search) {
            $query = $this->model->getTable();
            $total = $query->count();
            $pagination = $this->paginate($payload, $total);

            // ADD CUSTOM QUERIES HERE:

            // $query
            //     ->when()
            //     ->whereHas()
            //     ->with()

            // ALWAYS REDECLARE TOTAL AFTER A CUSTOM QUERY FOR RECOUNT
            // $total = $query->count();

            // ADD CUSTOM ORDER BY HERE

            // if ($latest) {
            //     $query->orderByDesc('relation.created_at');
            // } else {
            //     $query->orderByDesc('created_at');
            // }

            $ids = $query
                ->skip($pagination['skip'])
                ->take($pagination['take'])
                ->get()
                ->pluck('id');

            $ids = implode(',', $ids->toArray());

            $data = $this->model::search($search, function ($meilisearch, $query, $options) use ($ids) {
                $options['filter'] = 'id IN ['.$ids.']';
                $options['sort'] = [
                    'created_at:desc'
                ];
                return $meilisearch->search($query, $options);
            })->raw();

        } else {
            $pagination = $this->paginate($payload, 1000);
            $data = $this->model::search($search, function ($meilisearch, $query, $options) use ($filterString, $pagination, $sort) {
                if ($filterString) {
                    $options['filter'] = $filterString;
                }
                if ($sort) {
                    $options['sort'] = $sort;
                } else {
                    $options['sort'] = [
                        'resolutions_created_at:desc'
                    ];
                }
                $options['offset'] = $pagination['skip'];
                $options['limit'] = $pagination['take'];
                return $meilisearch->search($query, $options);
            })->raw();

            $total = $data['estimatedTotalHits'];
        }

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
            'headers' => null,
            'body' => $data['hits'],
            'searchable' => null,
            'others'    => [
                'view'          => Bouncer::can($ability.'.show'),
                'store'         => Bouncer::can($ability.'.store'),
                'update'        => Bouncer::can($ability.'.update'),
                'delete'        => Bouncer::can($ability.'.destroy')
            ]
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
            'body' => [],
            'searchable' => null,
        ]);
    }

    public function store($payload): array
    {
        DB::beginTransaction();
        try {
            $data = $this->model::create($payload);

            $model_name = $this->model->getTable();

            $result = $this->index(['included_id' => [$data['id']]] );

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

    public function show($id): array
    {
        $payload = ['included_id' => [$id]];

        $result = $this->index($payload);

        if ($result['body'])
            $result['message'] = 'Showing data.';

        return $result;
    }

    public function edit($id): array
    {
        $payload = ['included_id' => [$id]];

        $result = $this->index($payload);

        if ($result['body'])
            $result['message'] = 'Editing data.';

        return $result;
    }

    public function update($payload, $id): array
    {
        // TODO add a checker if multiple people is updating same id. Should not update if the current update is spoiled.

        $data = $this->model::find($id);

        if (!$data) {
            return [
                'message' => 'No found data.',
                'status' => 404,
            ];
        }

        DB::beginTransaction();

        try {
            $data->update($payload);

            $result = $this->index(['included_id' => [$id]]);
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

        return $result;
    }

    public function softDelete($id): array
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
            $old = $this->index(['included_id' => [$id]]);
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
            'body' => $old['body'],
            'searchable' => null,
        ]);
    }

    public function permanentDelete($id): array
    {
        $data = $this->model::when(in_array(SoftDeletes::class, class_uses($this->model)), function ($q) {
                    $q->withTrashed(); })
                ->find($id);

        if (!$data) {
            return [
                'message' => 'No found data.',
                'status' => 404,
            ];
        }

        $payload = ['included_id' => [$id]];

        // TODO add checking for relations before permanent deletion.
        DB::beginTransaction();
        try {
            $old = $this->index(['included_id' => [$id]]);
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
            'body' => $old['body'],
            'searchable' => null,
        ]);
    }

    public function restore($id): array
    {
        $data = $this->model::withTrashed()->find($id);

        if (!$data) {
            return [
                'message' => 'No found data.',
                'status' => 404,
            ];
        }

        DB::beginTransaction();
        try {
            $data->restore();
        } catch (\Exception $exception) {
            DB::rollBack();
            // Please review the Logs if there are errors.
            return [
                'message' => 'An error occurred while storing',
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
            'body' => $this->index(['included_id' => [$id]])['body'],
            'searchable' => null,
        ]);
    }

    // LIES CUSTOM QUERY GENERATORS HERE

    public function paginate($payload, $total): array
    {
        $current_page = isset($payload['page']) ? (is_numeric($payload['page']) && $payload['page'] > 0) ? $payload['page'] : 1 : 1;
        $take = isset($payload['show']) ? (is_numeric($payload['show']) ? $payload['show'] : (($payload['show'] == 'all') ? $this->model->count() : $total)) : 15;
        $skip = (is_numeric($take) ? $take : 0) * ((isset($payload['page']) && $payload['page'] > 1) ? ($payload['page'] - 1) : 0);

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
}
