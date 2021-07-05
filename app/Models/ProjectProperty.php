<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectProperty extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'color',
        'bg_color',
        'icon_id',
        'price'
    ];

    public function projects()
    {
        return $this->belongsToMany(Project::class);
    }

    public function icon()
    {
        return $this->morphOne(Image::class, 'imageable', 'imageable_type', 'id', 'icon_id');
    }
}
