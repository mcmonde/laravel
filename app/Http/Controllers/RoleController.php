<?php

namespace App\Http\Controllers;

use App\Http\Requests\Role\Create;
use App\Http\Requests\Role\Destroy;
use App\Http\Requests\Role\Edit;
use App\Http\Requests\Role\ForceDelete;
use App\Http\Requests\Role\Index;
use App\Http\Requests\Role\Restore;
use App\Http\Requests\Role\Show;
use App\Http\Requests\Role\Store;
use App\Http\Requests\Role\Update;
use App\Repositories\RoleRepository;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    protected RoleRepository $RoleRepository;
    protected array $selected_relation_columns_only = [
//            "suppliers_encoded_by_foreign" => ['id', 'name', 'email']
    ];
    protected array $headers = [
        ['text' => 'Unique Name', 'value' => 'roles_name', 'align' => 'left', 'sortable' => false],
        ['text' => 'Name', 'value' => 'roles_title', 'align' => 'left', 'sortable' => false],
    ];

    public function __construct(RoleRepository $RoleRepository)
    {
        $this->RoleRepository = $RoleRepository;
    }

    public function index(Index $request): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->RoleRepository->index($payload, $this->selected_relation_columns_only, $this->headers);
        return $this->RoleRepository->getJsonResponse($data);
    }

    public function create(Create $request): JsonResponse
    {
        $data = $this->RoleRepository->create();
        return $this->RoleRepository->getJsonResponse($data);
    }

    public function store(Store $request): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->RoleRepository->store($payload, $this->selected_relation_columns_only, $this->headers);
        return $this->RoleRepository->getJsonResponse($data);
    }

    public function show(Show $request, $id): JsonResponse
    {
        $data = $this->RoleRepository->show($id, $this->selected_relation_columns_only);
        return $this->RoleRepository->getJsonResponse($data);
    }

    public function edit(Edit $request, $id): JsonResponse
    {
        $data = $this->RoleRepository->edit($id, $this->selected_relation_columns_only);
        return $this->RoleRepository->getJsonResponse($data);
    }

    public function update(Update $request, $id): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->RoleRepository->update($payload, $id, $this->selected_relation_columns_only, $this->headers);
        return $this->RoleRepository->getJsonResponse($data);
    }

    public function destroy(Destroy $request, $id): JsonResponse
    {
        $data = $this->RoleRepository->destroy($id, $this->selected_relation_columns_only);
        return $this->RoleRepository->getJsonResponse($data);
    }

    public function forceDelete(ForceDelete $request, $id): JsonResponse
    {
        $data = $this->RoleRepository->forceDelete($id, $this->selected_relation_columns_only);
        return $this->RoleRepository->getJsonResponse($data);
    }

    public function restore(Restore $request, $id): JsonResponse
    {
        $data = $this->RoleRepository->restore($id, $this->selected_relation_columns_only);
        return $this->RoleRepository->getJsonResponse($data);
    }
}
