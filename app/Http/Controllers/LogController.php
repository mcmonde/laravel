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
    protected LogRepository $LogRepository;
    protected array $selected_relation_columns_only = [
//            "suppliers_encoded_by_foreign" => ['id', 'first_name', 'middle_name', 'last_name', 'email']
        ];
    protected array $headers = [
//            ['text' => 'Supplier', 'value' => 'suppliers_name', 'align' => 'left', 'sortable' => false],
        ];

    public function __construct(LogRepository $LogRepository)
    {
        $this->LogRepository = $LogRepository;
    }

    public function index(Index $request): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->LogRepository->index($payload, $this->selected_relation_columns_only, $this->headers);
        return $this->LogRepository->getJsonResponse($data);
    }

    public function create(Create $request): JsonResponse
    {
        $data = $this->LogRepository->create();
        return $this->LogRepository->getJsonResponse($data);
    }

    public function store(Store $request): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->LogRepository->store($payload, $this->selected_relation_columns_only, $this->headers);
        return $this->LogRepository->getJsonResponse($data);
    }

    public function show(Show $request, $id): JsonResponse
    {
        $data = $this->LogRepository->show($id, $this->selected_relation_columns_only);
        return $this->LogRepository->getJsonResponse($data);
    }

    public function edit(Edit $request, $id): JsonResponse
    {
        $data = $this->LogRepository->edit($id, $this->selected_relation_columns_only);
        return $this->LogRepository->getJsonResponse($data);
    }

    public function update(Update $request, $id): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->LogRepository->update($payload, $id, $this->selected_relation_columns_only, $this->headers);
        return $this->LogRepository->getJsonResponse($data);
    }

    public function destroy(Destroy $request, $id): JsonResponse
    {
        $data = $this->LogRepository->destroy($id, $this->selected_relation_columns_only);
        return $this->LogRepository->getJsonResponse($data);
    }

    public function forceDelete(ForceDelete $request, $id): JsonResponse
    {
        $data = $this->LogRepository->forceDelete($id, $this->selected_relation_columns_only);
        return $this->LogRepository->getJsonResponse($data);
    }

    public function restore(Restore $request, $id): JsonResponse
    {
        $data = $this->LogRepository->restore($id, $this->selected_relation_columns_only);
        return $this->LogRepository->getJsonResponse($data);
    }
}
