<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorageType\Create;
use App\Http\Requests\StorageType\Destroy;
use App\Http\Requests\StorageType\Edit;
use App\Http\Requests\StorageType\ForceDelete;
use App\Http\Requests\StorageType\Index;
use App\Http\Requests\StorageType\Restore;
use App\Http\Requests\StorageType\Show;
use App\Http\Requests\StorageType\Store;
use App\Http\Requests\StorageType\Update;
use App\Repositories\StorageTypeRepository;
use Illuminate\Http\JsonResponse;

class StorageTypeController extends Controller
{
    protected StorageTypeRepository $StorageTypeRepository;
    protected array $selected_relation_columns_only = [
//            "suppliers_encoded_by_foreign" => ['id', 'first_name', 'middle_name', 'last_name', 'email']
        ];
    protected array $headers = [
//            ['text' => 'Supplier', 'value' => 'suppliers_name', 'align' => 'left', 'sortable' => false],
        ];

    public function __construct(StorageTypeRepository $StorageTypeRepository)
    {
        $this->StorageTypeRepository = $StorageTypeRepository;
    }

    public function index(Index $request): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->StorageTypeRepository->index($payload, $this->selected_relation_columns_only, $this->headers);
        return $this->StorageTypeRepository->getJsonResponse($data);
    }

    public function create(Create $request): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->StorageTypeRepository->create();
        return $this->StorageTypeRepository->getJsonResponse($data);
    }

    public function store(Store $request): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->StorageTypeRepository->store($payload, $this->selected_relation_columns_only, $this->headers);
        return $this->StorageTypeRepository->getJsonResponse($data);
    }

    public function show(Show $request, $id): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->StorageTypeRepository->show($id, $this->selected_relation_columns_only);
        return $this->StorageTypeRepository->getJsonResponse($data);
    }

    public function edit(Edit $request, $id): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->StorageTypeRepository->edit($id, $this->selected_relation_columns_only);
        return $this->StorageTypeRepository->getJsonResponse($data);
    }

    public function update(Update $request, $id): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->StorageTypeRepository->update($payload, $id, $this->selected_relation_columns_only, $this->headers);
        return $this->StorageTypeRepository->getJsonResponse($data);
    }

    public function destroy(Destroy $request, $id): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->StorageTypeRepository->destroy($id, $this->selected_relation_columns_only);
        return $this->StorageTypeRepository->getJsonResponse($data);
    }

//  DEPRICATED. NO LONGER IN USE UNLESS ITS REALLY NECESSARY.

//    public function forceDelete(ForceDelete $request, $id): JsonResponse
//    {
//        $data = $this->StorageTypeRepository->forceDelete($id, $this->selected_relation_columns_only);
//        return $this->StorageTypeRepository->getJsonResponse($data);
//    }
//
//    public function restore(Restore $request, $id): JsonResponse
//    {
//        $data = $this->StorageTypeRepository->restore($id, $this->selected_relation_columns_only);
//        return $this->StorageTypeRepository->getJsonResponse($data);
//    }
}
