<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory, Sluggable;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'slug'
    ];

    /**
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    public function sluggable()
    {
        return [
            'slug' => [
                'source' => 'name'
            ]
        ];
    }

    public function articles()
    {
//        return $this->morphedByMany(Article::class, 'taggable');
        return $this->belongsToMany(Article::class);
    }
}
