<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Traits\SeederFileHandler;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    use SeederFileHandler;

    protected ModelNameHere $model;

    public function __construct(ModelNameHere $model)
    {
        $this->model = $model;
    }

    public function run(): void
    {
        $this->GenerateData($this->model->getTable());
    }
}
