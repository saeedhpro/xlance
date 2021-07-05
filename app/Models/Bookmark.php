<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bookmark extends Model
{
    use HasFactory;

    public $timestamps = false;
    public $guarded = [];

    public function scopeWithType(Builder $query, $type)
    {
        return $query->where('model_type', $type);
    }
}
