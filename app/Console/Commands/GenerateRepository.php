<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateRepository extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:repository {modelName}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make repository class. [ app:repository {modelName} ]';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $model_name = ucfirst($this->argument('modelName'));
        $directory = app_path('Repositories');

        if (!File::isDirectory($directory))
            File::makeDirectory($directory, 0755, true);

        $file_path = "{$directory}/{$model_name}Repository.php";

        if (!file_exists($file_path)) {
            File::put($file_path, $this->generateRepositoryStub($model_name));
            $this->info("Repository file {$file_path} generated.");
        } else
            $this->error("{$file_path} already exist. Skipped.");
    }

    protected function generateRepositoryStub($model_name): string
    {
        $stub_content = File::get(base_path('stubs/repository.stub'));
        $replacements = [
            'namespace' => "App\\Repositories",
            'class' => ucfirst($model_name)
        ];

        foreach ($replacements as $key => $value)
            $stub_content = str_replace("{{ $key }}", $value, $stub_content);

        return $stub_content;
    }
}
