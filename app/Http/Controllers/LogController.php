<?php

namespace App\Http\Controllers;

use App\Http\Requests\Log\Create;
use App\Http\Requests\Log\Destroy;
use App\Http\Requests\Log\Edit;
use App\Http\Requests\Log\ForceDelete;
use App\Http\Requests\Log\Index;
use App\Http\Requests\Log\Restore;
use App\Http\Requests\Log\Show;
use App\Http\Requests\Log\Store;
use App\Http\Requests\Log\Update;
use App\Repositories\LogRepository;
use Illuminate\Http\JsonResponse;

class LogController extends Controller
{
    protected LogRepository $repository;
    protected array $selected_relation_columns_only = [
//            "suppliers_encoded_by_foreign" => ['id', 'first_name', 'middle_name', 'last_name', 'email']
        ];
    protected array $headers = [
//            ['text' => 'Supplier', 'value' => 'suppliers_name', 'align' => 'left', 'sortable' => false],
        ];

    public function __construct(LogRepository $repository)
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

    public function destroy(Destroy $request, $id): JsonResponse
    {
        $data = $this->repository->destroy($id, $this->selected_relation_columns_only);
        return $this->repository->getJsonResponse($data);
    }

    public function forceDelete(ForceDelete $request, $id): JsonResponse
    {
        $data = $this->repository->forceDelete($id, $this->selected_relation_columns_only);
        return $this->repository->getJsonResponse($data);
    }

    public function restore(Restore $request, $id): JsonResponse
    {
        $data = $this->repository->restore($id, $this->selected_relation_columns_only);
        return $this->repository->getJsonResponse($data);
    }
}
