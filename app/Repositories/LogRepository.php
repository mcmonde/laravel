<?php

namespace App\Repositories;

use App\Models\Log;
use App\Traits\QueryGenerator;

class LogRepository
{
    use QueryGenerator;

    protected Log $model;

    public function __construct(Log $model)
    {
        $this->model = $model;
    }

//    public function index($request, $selected_relation_columns_only = [], $headers = []): array
//    {
//        // override parent method here;
//    }
//
//    public function create(): array
//    {
//        // override parent method here;
//    }
//
//    public function store($request): array
//    {
//        // override parent method here;
//    }
//
//    public function show($id): array
//    {
//        // override parent method here;
//    }
//
//    public function edit($id): array
//    {
//        // override parent method here;
//    }
//
//    public function update($request, $id): array
//    {
//        // override parent method here;
//    }
//
//    public function destroy($id): array
//    {
//        // override parent method here;
//    }
//
//    public function forceDelete($id): array
//    {
//        // override parent method here;
//    }
//
//    public function restore($id): array
//    {
//        // override parent method here;
//    }
}
