<?php

namespace App\Http\Controllers;

use App\Http\Requests\Role\Create;
use App\Http\Requests\Role\SoftDelete;
use App\Http\Requests\Role\Edit;
use App\Http\Requests\Role\PermanentDelete;
use App\Http\Requests\Role\Index;
use App\Http\Requests\Role\Restore;
use App\Http\Requests\Role\Show;
use App\Http\Requests\Role\Store;
use App\Http\Requests\Role\Update;
use App\Repositories\RoleRepository;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    protected RoleRepository $repository;

    public function __construct(RoleRepository $repository)
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
        $data = $this->repository->update($payload, $id);
        return $this->repository->getJsonResponse($data);
    }

    public function softDelete(SoftDelete $request, $id): JsonResponse
    {
        $data = $this->repository->softDelete($id);
        return $this->repository->getJsonResponse($data);
    }

    public function permanentDelete(PermanentDelete $request, $id): JsonResponse
    {
        $data = $this->repository->permanentDelete($id);
        return $this->repository->getJsonResponse($data);
    }

    public function restore(Restore $request, $id): JsonResponse
    {
        $data = $this->repository->restore($id);
        return $this->repository->getJsonResponse($data);
    }
}
