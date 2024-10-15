<?php

namespace App\Repositories;

use App\Models\Ability;
use App\Traits\QueryGenerator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Silber\Bouncer\Database\Role;
use Bouncer;

class AbilityRepository
{
    use QueryGenerator;

    protected Ability $model;

    public function __construct(Ability $model)
    {
        $this->model = $model;
    }

    public function create(): array
    {
        return ([
            'message' => 'Creating data.',
            'error' => null,
            'current_page' => null,
            'from' => null,
            'to' => null,
            'last_page' => null,
            'skip' => null,
            'take' => null,
            'total' => null,
            'headers' => null,
            'body' => [
                'columns' => $this->getTableColumnDetails(
                    $this->model->getTable(),
                    ['id', 'entity_id', 'entity_type', 'only owned', 'options', 'scope', 'only_owned', 'created_at', 'updated_at']
                ),
                'abilities' => $this->getAbilitiesSubstring(),
            ],
            'searchable' => null,
        ]);
    }

    // LIES CUSTOM ABILITY PROCESS HERE

    public function getAllAbilities(): array
    {
        $abilities = Bouncer::ability()->all(['id', 'name', 'title'])->groupBy(function ($item) {
            // Extract the grouping key based on the pattern using periods
            preg_match('/^([^.]+)\./', $item['name'], $matches);

            // Return the part before the first period as the key for grouping
            return $matches[1] ?? '*';
        })->toArray();

        return $abilities;
    }

    public function getDataAbilities($data): array
    {
        $allowed = $data->getAbilities()->map(function ($ability) {
            // Extract only the desired attributes
            return $ability->only(['id', 'name', 'title']);
        })->toArray();

        $all = Bouncer::ability()->all(['id', 'name', 'title'])->toArray();

        foreach ($all as &$ability) {
            $ability['check'] = false;
            foreach ($allowed as $allow) {
                if ($ability['id'] == $allow['id']) {
                    $ability['check'] = true;
                    break;
                }
            }
        }

        return collect($all)->groupBy(function ($ability) {
            // Extract the common name pattern using regular expression
            preg_match('/^([a-z0-9-]+)\.[a-z0-9-]+$/', $ability['name'], $matches);
            return $matches[1] ?? '*';
        })->toArray();
    }

    public function getUserAbilities(): Collection
    {
        $abilities = auth()->user()->getAbilities();

        if ($abilities->isNotEmpty() && $abilities[0]->name == '*') {
            $abilities = Bouncer::ability()->all('id', 'name', 'title')->reject(function ($item) {
                return $item->name == '*';
            });
        } else
            $abilities = $abilities->reject(function ($item) {
                return $item->name == '*';
            });

        return $abilities;
    }

    public function getAbilitiesSubstring(): array
    {
        return DB::table(function ($query) {
            $query->select(DB::raw('DISTINCT SPLIT_PART(name, \'.\', 1) AS first_part'))
                ->from('abilities');
        }, 'sub')
            ->where('first_part', '<>', '*')
            ->orderBy('first_part')
            ->get()
            ->pluck('first_part')
            ->toArray();
    }

    public function getAbilityUrl($payload): array
    {
        $role = $payload['role_name'];
        // Retrieve all routes
        $allRoutes = app('router')->getRoutes();
        $role = Role::where('name', $role)->first();
        $abilities = $role->getAbilities();

        if ($abilities->isNotEmpty() && $abilities[0]->name == '*') {
            $abilities = Bouncer::ability()->all()->reject(function ($item) {
                return $item->name == '*'
                    || (preg_match('/\.(create|store|show|edit|update|destroy|force-delete|restore)$/', $item->name));
            });
        } else
            $abilities = $abilities->reject(function ($item) {
                return $item->name == '*'
                    || (preg_match('/\.(create|store|show|edit|update|destroy|force-delete|restore)$/', $item->name));
            });

        // Filter only API routes
        $apiRoutes = collect($allRoutes)->filter(function ($route) {
            return str_starts_with($route->uri(), 'api/');
        });

        $tabs = [];
        foreach ($apiRoutes as $route) {
            foreach ($abilities as $ability)
                if ($ability->name == $route->getName())

                    $tabs[] = [
                        'id' => $ability->id,
                        //                        'name' => $ability->name,
                        'title' => ucfirst(str_replace('-', ' ', Str::singular(explode('.', $route->getName())[0]))),
                        'name' => $route->getName(),
                        //                        'method' => $route->methods(),
                        'uri' => str_replace('api', '', $route->uri()),
                        //                        'action' => $route->getActionName(),
                        //                        'middleware' => $route->middleware(),
                    ];
        }

        return $tabs;
    }
}
