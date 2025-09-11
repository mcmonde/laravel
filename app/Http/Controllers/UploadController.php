<?php

namespace App\Http\Controllers;

use App\Http\Requests\Upload\Create;
use App\Http\Requests\Upload\Destroy;
use App\Http\Requests\Upload\Edit;
use App\Http\Requests\Upload\ForceDelete;
use App\Http\Requests\Upload\Index;
use App\Http\Requests\Upload\Restore;
use App\Http\Requests\Upload\Show;
use App\Http\Requests\Upload\Store;
use App\Http\Requests\Upload\Update;
use App\Repositories\UploadRepository;
use Illuminate\Http\JsonResponse;

class UploadController extends Controller
{
//    protected UploadRepository $UploadRepository;
    protected array $selected_relation_columns_only = [
//            "suppliers_encoded_by_foreign" => ['id', 'first_name', 'middle_name', 'last_name', 'email']
        ];
    protected array $headers = [
//            ['text' => 'Supplier', 'value' => 'suppliers_name', 'align' => 'left', 'sortable' => false],
        ];

//    public function __construct(UploadRepository $UploadRepository)
//    {
//        $this->UploadRepository = $UploadRepository;
//    }

    public function index(Index $request): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->UploadRepository->index($payload, $this->selected_relation_columns_only, $this->headers);
        return $this->UploadRepository->getJsonResponse($data);
    }

    public function create(Create $request): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->UploadRepository->create();
        return $this->UploadRepository->getJsonResponse($data);
    }

    public function store(Store $request): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->UploadRepository->store($payload, $this->selected_relation_columns_only, $this->headers);
        return $this->UploadRepository->getJsonResponse($data);
    }

    public function show(Show $request, $id): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->UploadRepository->show($id, $this->selected_relation_columns_only);
        return $this->UploadRepository->getJsonResponse($data);
    }

    public function edit(Edit $request, $id): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->UploadRepository->edit($id, $this->selected_relation_columns_only);
        return $this->UploadRepository->getJsonResponse($data);
    }

    public function update(Update $request, $id): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->UploadRepository->update($payload, $id, $this->selected_relation_columns_only, $this->headers);
        return $this->UploadRepository->getJsonResponse($data);
    }

    public function destroy(Destroy $request, $id): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->UploadRepository->destroy($id, $this->selected_relation_columns_only);
        return $this->UploadRepository->getJsonResponse($data);
    }

//  DEPRICATED. NO LONGER IN USE UNLESS ITS REALLY NECESSARY.

//    public function forceDelete(ForceDelete $request, $id): JsonResponse
//    {
//        $data = $this->UploadRepository->forceDelete($id, $this->selected_relation_columns_only);
//        return $this->UploadRepository->getJsonResponse($data);
//    }
//
//    public function restore(Restore $request, $id): JsonResponse
//    {
//        $data = $this->UploadRepository->restore($id, $this->selected_relation_columns_only);
//        return $this->UploadRepository->getJsonResponse($data);
//    }
}
