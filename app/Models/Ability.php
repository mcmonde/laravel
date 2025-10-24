<?php

namespace App\Models;

use App\Traits\HasDynamicFillable;
use App\Traits\HasDynamicRelations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ability extends Model
{
    use HasFactory,
        HasDynamicFillable,
        HasDynamicRelations,
        SoftDeletes;

    protected $guarded = ['id', 'created_at', 'updated_at'];
}
