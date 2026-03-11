<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use App\Traits\HasDynamicFillable;
use App\Traits\HasDynamicRelations;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Silber\Bouncer\Database\HasRolesAndAbilities;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory,
        Notifiable,
        HasApiTokens,
        HasRolesAndAbilities,
        HasDynamicFillable,
        HasDynamicRelations,
        SoftDeletes;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

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
