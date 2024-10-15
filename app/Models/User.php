<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\HasApiTokens;
use Silber\Bouncer\Database\HasRolesAndAbilities;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasApiTokens, HasRolesAndAbilities, SoftDeletes;

    protected $fillable = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $cache_name = "User_model_columns";

        // Check if the column listing is cached
        if (!Cache::has($cache_name)) {
            // If not cached, retrieve the column listing and cache it
            $columns = array_diff(Schema::getColumnListing($this->getTable()),['id','created_at','updated_at','deleted_at']);
            Cache::forever($cache_name, $columns); // Cache the column listing indefinitely
        } else {
            // If cached, retrieve the column listing from the cache
            $columns = Cache::get($cache_name);
        }

        $this->fillable = $columns;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function userRoles(): BelongsToMany
    {
        return $this->belongsToMany('Silber\Bouncer\Database\Role', 'assigned_roles', 'entity_id', 'role_id')
            ->where('entity_type', 'App\Models\User');
//            ->withPivot('restricted_to_id','restricted_to_type', 'scope')
//            ->as('assigned_roles');
    }
}
