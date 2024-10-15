<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\Create;
use App\Http\Requests\User\Destroy;
use App\Http\Requests\User\Edit;
use App\Http\Requests\User\ForceDelete;
use App\Http\Requests\User\Index;
use App\Http\Requests\User\Restore;
use App\Http\Requests\User\Show;
use App\Http\Requests\User\Store;
use App\Http\Requests\User\Update;
use App\Repositories\UserRepository;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    protected UserRepository $UserRepository;
    protected array $selected_relation_columns_only = [
        'users' => ['id', 'first_name', 'middle_name', 'last_name', 'email', 'address', 'contact_number', 'status', 'allow_login', 'created_by', 'created_at', 'updated_at', 'deleted_at', 'tin'],
        'users_created_by_foreign' => ['id', 'first_name', 'middle_name', 'last_name'],
    ];
    protected array $headers = [
        ['text' => 'Name', 'value' => 'full_name', 'align' => '', 'sortable' => false],
        ['text' => 'Address', 'value' => 'users_address', 'align' => '', 'sortable' => false],
        ['text' => 'Contact Number', 'value' => 'users_contact_number', 'align' => '', 'sortable' => false],
        ['text' => 'Email', 'value' => 'users_email', 'align' => '', 'sortable' => false],
        ['text' => 'Tin', 'value' => 'users_tin', 'align' => '', 'sortable' => false],
        ['text' => 'Role', 'value' => 'roles', 'align' => '', 'sortable' => false],
        ['text' => 'Status', 'value' => 'users_status', 'align' => '', 'sortable' => false],
        ['text' => 'Allow Login', 'value' => 'users_allow_login', 'align' => '', 'sortable' => false],
        ['text' => 'Created by', 'value' => 'created_by_full_name', 'align' => '', 'sortable' => false],
    ];

    public function __construct(UserRepository $UserRepository)
    {
        $this->UserRepository = $UserRepository;
    }

    public function index(Index $request): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->UserRepository->index($payload, $this->selected_relation_columns_only, $this->headers);
        return $this->UserRepository->getJsonResponse($data);
    }

    public function create(Create $request): JsonResponse
    {
        $data = $this->UserRepository->create();
        return $this->UserRepository->getJsonResponse($data);
    }

    public function store(Store $request): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->UserRepository->store($payload, $this->selected_relation_columns_only, $this->headers);
        return $this->UserRepository->getJsonResponse($data);
    }

    public function show(Show $request, $id): JsonResponse
    {
        $data = $this->UserRepository->show($id, $this->selected_relation_columns_only, $this->headers);
        return $this->UserRepository->getJsonResponse($data);
    }

    public function edit(Edit $request, $id): JsonResponse
    {
        $data = $this->UserRepository->edit($id, $this->selected_relation_columns_only);
        return $this->UserRepository->getJsonResponse($data);
    }

    public function update(Update $request, $id): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->UserRepository->update($payload, $id, $this->selected_relation_columns_only, $this->headers);
        return $this->UserRepository->getJsonResponse($data);
    }

    public function destroy(Destroy $request, $id): JsonResponse
    {
        $data = $this->UserRepository->destroy($id, $this->selected_relation_columns_only);
        return $this->UserRepository->getJsonResponse($data);
    }

    public function forceDelete(ForceDelete $request, $id): JsonResponse
    {
        $data = $this->UserRepository->forceDelete($id, $this->selected_relation_columns_only);
        return $this->UserRepository->getJsonResponse($data);
    }

    public function restore(Restore $request, $id): JsonResponse
    {
        $data = $this->UserRepository->restore($id, $this->selected_relation_columns_only);
        return $this->UserRepository->getJsonResponse($data);
    }
}
