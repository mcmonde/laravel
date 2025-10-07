<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Venturecraft\Revisionable\RevisionableTrait;

class StorageType extends Model
{
    use HasFactory, SoftDeletes, RevisionableTrait;

    protected $fillable = [];
    protected $casts = ['config' => 'array'];
    protected $revisionEnabled = true;
    protected $revisionCreationsEnabled = true;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $cache_name = "StorageType_model_columns";

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

    public function uploads(): HasMany
    {
        return $this->hasMany(Upload::class);
    }
}
