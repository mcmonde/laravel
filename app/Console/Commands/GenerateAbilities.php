<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Bouncer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GenerateAbilities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:abilities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "generate abilities for controller's methods.";

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $super_admin = Bouncer::role()->firstOrCreate([
            'name' => 'super-admin',
            'title' => 'Super Administrator',
        ]);

        $super_admin->wasRecentlyCreated? $this->info('Role super-admin created.') : $this->error('Role super-admin existed. Skipped.');

        Bouncer::allow('super-admin')->everything();

        User::updateOrCreate([
            'email'             =>config('account.email','admin@admin.com'),
        ], [
            'first_name'        => config('account.first_name','Super '),
            'middle_name'       => config('account.middle_name',''),
            'last_name'         => config('account.last_name','Admin'),
            'email'             => config('account.email','admin@admin.com'),
            'email_verified_at' => now(),
            'remember_token'    => Str::random(99),
            'password'          => Hash::make(config('account.password','password'))
        ]);

        Bouncer::assign('super-admin')->to(User::where('email', config('account.email','admin@admin.com'))->first());

        $methods = ['Index','Create','Store','Show','Edit','Update','SoftDelete','PermanentDelete','Restore', 'Upload'];
        $controllerDirectory = app_path('Http/Controllers');
        $controllerFiles = scandir($controllerDirectory);
        $excluded =['Auth'];

        foreach ($controllerFiles as $controllerFile) {
            if (is_file($controllerDirectory.'/'.$controllerFile)) {
                // Remove the ".php" extension and "Controller" postfix
                $controllerName = pathinfo($controllerFile, PATHINFO_FILENAME);
                $name_case = str_replace('Controller', '', $controllerName);

                if ($name_case != "" && !in_array($name_case,$excluded)) {
                    $model_name = ucfirst($name_case);

                    foreach ($methods as $method) {
                        $this->createAbilities($method, $model_name);
                    }
                }
            }
        }

        // CUSTOM ABILITIES
        $this->createAbilities('Current','abilities');

    }

    public function createAbilities($method, $model_name): void
    {
        $ability = Str::plural(Str::snake($model_name, '-')).'.'.strtolower(Str::kebab($method));

        $created = Bouncer::ability()->firstOrCreate([
            'name' => $ability,
            'title' =>  Str::title($method == 'Index'? 'Browse '.(Str::plural(Str::snake($model_name,'-'))) : Str::snake($method,' ').' '.(Str::snake($model_name,'-'))),
        ]);

        if (!$created->wasRecentlyCreated) {
            $this->error('Ability '.$created->name.' already existed. Skipped.');
        } else
            $this->info('Ability '.$created->name.' created.');
    }
}
