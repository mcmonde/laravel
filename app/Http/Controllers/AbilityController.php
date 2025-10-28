<?php

namespace App\Http\Controllers;

use App\Http\Requests\Ability\Create;
use App\Http\Requests\Ability\Softdelete;
use App\Http\Requests\Ability\Edit;
use App\Http\Requests\Ability\PermanentDelete;
use App\Http\Requests\Ability\Index;
use App\Http\Requests\Ability\Restore;
use App\Http\Requests\Ability\Show;
use App\Http\Requests\Ability\Store;
use App\Http\Requests\Ability\Update;
use App\Http\Requests\Ability\Current;
use App\Repositories\AbilityRepository;
use Illuminate\Http\JsonResponse;

class AbilityController extends Controller
{
    protected AbilityRepository $repository;
    protected array $selected_relation_columns_only = [
        'abilities'   => ['id','name','title'],
    ];
    protected array $headers = [
        ['text' => 'Unique Name', 'value' => 'abilities_name', 'align' => 'left', 'sortable' => false],
        ['text' => 'Title', 'value' => 'abilities_title', 'align' => 'left', 'sortable' => false],
    ];

    public function __construct(AbilityRepository $repository)
    {
        $this->repository = $repository;
    }

    public function index(Index $request): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->repository->index($payload, $this->selected_relation_columns_only, $this->headers);
        return $this->repository->getJsonResponse($data);
    }

    public function create(Create $request): JsonResponse
    {
        $data = $this->repository->create();
        return $this->repository->getJsonResponse($data);
    }

    public function store(Store $request): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->repository->store($payload, $this->selected_relation_columns_only, $this->headers);
        return $this->repository->getJsonResponse($data);
    }

    public function show(Show $request, $id): JsonResponse
    {
        $data = $this->repository->show($id, $this->selected_relation_columns_only);
        return $this->repository->getJsonResponse($data);
    }

    public function edit(Edit $request, $id): JsonResponse
    {
        $data = $this->repository->edit($id, $this->selected_relation_columns_only);
        return $this->repository->getJsonResponse($data);
    }

    public function update(Update $request, $id): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->repository->update($payload, $id, $this->selected_relation_columns_only, $this->headers);
        return $this->repository->getJsonResponse($data);
    }

    public function softDelete(SoftDelete $request, $id): JsonResponse
    {
        $data = $this->repository->softDelete($id, $this->selected_relation_columns_only);
        return $this->repository->getJsonResponse($data);
    }

    public function permanentDelete(PermanentDelete $request, $id): JsonResponse
    {
        $data = $this->repository->permanentDelete($id, $this->selected_relation_columns_only);
        return $this->repository->getJsonResponse($data);
    }

    public function restore(Restore $request, $id): JsonResponse
    {
        $data = $this->repository->restore($id, $this->selected_relation_columns_only);
        return $this->repository->getJsonResponse($data);
    }

    public function getCurrentAbilities(Current $request): JsonResponse
    {
        $data = $this->repository->getAbilityUrl($request);
        return $this->repository->getJsonResponse($data);
    }
}
