<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadCategory\Create;
use App\Http\Requests\UploadCategory\Destroy;
use App\Http\Requests\UploadCategory\Edit;
use App\Http\Requests\UploadCategory\ForceDelete;
use App\Http\Requests\UploadCategory\Index;
use App\Http\Requests\UploadCategory\Restore;
use App\Http\Requests\UploadCategory\Show;
use App\Http\Requests\UploadCategory\Store;
use App\Http\Requests\UploadCategory\Update;
use App\Repositories\UploadCategoryRepository;
use Illuminate\Http\JsonResponse;

class UploadCategoryController extends Controller
{
    protected UploadCategoryRepository $UploadCategoryRepository;
    protected array $selected_relation_columns_only = [
//            "suppliers_encoded_by_foreign" => ['id', 'first_name', 'middle_name', 'last_name', 'email']
        ];
    protected array $headers = [
//            ['text' => 'Supplier', 'value' => 'suppliers_name', 'align' => 'left', 'sortable' => false],
        ];

    public function __construct(UploadCategoryRepository $UploadCategoryRepository)
    {
        $this->UploadCategoryRepository = $UploadCategoryRepository;
    }

    public function index(Index $request): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->UploadCategoryRepository->index($payload, $this->selected_relation_columns_only, $this->headers);
        return $this->UploadCategoryRepository->getJsonResponse($data);
    }

    public function create(Create $request): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->UploadCategoryRepository->create();
        return $this->UploadCategoryRepository->getJsonResponse($data);
    }

    public function store(Store $request): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->UploadCategoryRepository->store($payload, $this->selected_relation_columns_only, $this->headers);
        return $this->UploadCategoryRepository->getJsonResponse($data);
    }

    public function show(Show $request, $id): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->UploadCategoryRepository->show($id, $this->selected_relation_columns_only);
        return $this->UploadCategoryRepository->getJsonResponse($data);
    }

    public function edit(Edit $request, $id): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->UploadCategoryRepository->edit($id, $this->selected_relation_columns_only);
        return $this->UploadCategoryRepository->getJsonResponse($data);
    }

    public function update(Update $request, $id): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->UploadCategoryRepository->update($payload, $id, $this->selected_relation_columns_only, $this->headers);
        return $this->UploadCategoryRepository->getJsonResponse($data);
    }

    public function destroy(Destroy $request, $id): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->UploadCategoryRepository->destroy($id, $this->selected_relation_columns_only);
        return $this->UploadCategoryRepository->getJsonResponse($data);
    }

//  DEPRICATED. NO LONGER IN USE UNLESS ITS REALLY NECESSARY.

//    public function forceDelete(ForceDelete $request, $id): JsonResponse
//    {
//        $data = $this->UploadCategoryRepository->forceDelete($id, $this->selected_relation_columns_only);
//        return $this->UploadCategoryRepository->getJsonResponse($data);
//    }
//
//    public function restore(Restore $request, $id): JsonResponse
//    {
//        $data = $this->UploadCategoryRepository->restore($id, $this->selected_relation_columns_only);
//        return $this->UploadCategoryRepository->getJsonResponse($data);
//    }
}
