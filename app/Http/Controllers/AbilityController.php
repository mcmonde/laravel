<?php

namespace App\Http\Controllers;

use App\Http\Requests\Ability\Create;
use App\Http\Requests\Ability\Destroy;
use App\Http\Requests\Ability\Edit;
use App\Http\Requests\Ability\ForceDelete;
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
    protected AbilityRepository $AbilityRepository;
    protected array $selected_relation_columns_only = [
        'abilities'   => ['id','name','title'],
    ];
    protected array $headers = [
        ['text' => 'Unique Name', 'value' => 'abilities_name', 'align' => 'left', 'sortable' => false],
        ['text' => 'Title', 'value' => 'abilities_title', 'align' => 'left', 'sortable' => false],
    ];

    public function __construct(AbilityRepository $AbilityRepository)
    {
        $this->AbilityRepository = $AbilityRepository;
    }

    public function index(Index $request): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->AbilityRepository->index($payload, $this->selected_relation_columns_only, $this->headers);
        return $this->AbilityRepository->getJsonResponse($data);
    }

    public function create(Create $request): JsonResponse
    {
        $data = $this->AbilityRepository->create();
        return $this->AbilityRepository->getJsonResponse($data);
    }

    public function store(Store $request): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->AbilityRepository->store($payload, $this->selected_relation_columns_only, $this->headers);
        return $this->AbilityRepository->getJsonResponse($data);
    }

    public function show(Show $request, $id): JsonResponse
    {
        $data = $this->AbilityRepository->show($id, $this->selected_relation_columns_only);
        return $this->AbilityRepository->getJsonResponse($data);
    }

    public function edit(Edit $request, $id): JsonResponse
    {
        $data = $this->AbilityRepository->edit($id, $this->selected_relation_columns_only);
        return $this->AbilityRepository->getJsonResponse($data);
    }

    public function update(Update $request, $id): JsonResponse
    {
        $payload = $request->validated();
        $data = $this->AbilityRepository->update($payload, $id, $this->selected_relation_columns_only, $this->headers);
        return $this->AbilityRepository->getJsonResponse($data);
    }

    public function destroy(Destroy $request, $id): JsonResponse
    {
        $data = $this->AbilityRepository->destroy($id, $this->selected_relation_columns_only);
        return $this->AbilityRepository->getJsonResponse($data);
    }

    public function forceDelete(ForceDelete $request, $id): JsonResponse
    {
        $data = $this->AbilityRepository->forceDelete($id, $this->selected_relation_columns_only);
        return $this->AbilityRepository->getJsonResponse($data);
    }

    public function restore(Restore $request, $id): JsonResponse
    {
        $data = $this->AbilityRepository->restore($id, $this->selected_relation_columns_only);
        return $this->AbilityRepository->getJsonResponse($data);
    }

    public function getCurrentAbilities(Current $request): JsonResponse
    {
        $data = $this->AbilityRepository->getAbilityUrl($request);
        return $this->AbilityRepository->getJsonResponse($data);
    }
}
