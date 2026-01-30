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

    public function __construct(AbilityRepository $repository)
    {
        $this->repository = $repository;
    }

    public function index(Index $request): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->repository->index($payload);
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
        $data = $this->repository->store($payload);
        return $this->repository->getJsonResponse($data);
    }

    public function show(Show $request, $id): JsonResponse
    {
        $data = $this->repository->show($id);
        return $this->repository->getJsonResponse($data);
    }

    public function edit(Edit $request, $id): JsonResponse
    {
        $data = $this->repository->edit($id);
        return $this->repository->getJsonResponse($data);
    }

    public function update(Update $request, $id): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->repository->update($payload, $id,);
        return $this->repository->getJsonResponse($data);
    }

    public function softDelete(SoftDelete $request, $id): JsonResponse
    {
        $data = $this->repository->softDelete($id);
        return $this->repository->getJsonResponse($data);
    }

    public function permanentDelete(PermanentDelete $request, $id): JsonResponse
    {
        $data = $this->repository->permanentDelete($id,);
        return $this->repository->getJsonResponse($data);
    }

    public function restore(Restore $request, $id): JsonResponse
    {
        $data = $this->repository->restore($id);
        return $this->repository->getJsonResponse($data);
    }

    public function getCurrentAbilities(Current $request): JsonResponse
    {
        $data = $this->repository->getAbilityUrl($request);
        return $this->repository->getJsonResponse($data);
    }
}
