<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\Create;
use App\Http\Requests\User\SoftDelete;
use App\Http\Requests\User\Edit;
use App\Http\Requests\User\PermanentDelete;
use App\Http\Requests\User\Index;
use App\Http\Requests\User\Restore;
use App\Http\Requests\User\Show;
use App\Http\Requests\User\Store;
use App\Http\Requests\User\Update;
use App\Repositories\UserRepository;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    protected UserRepository $repository;

    public function __construct(UserRepository $repository)
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
