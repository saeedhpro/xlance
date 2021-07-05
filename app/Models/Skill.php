<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
        'category_id',
        'status',
        'keywords',
        'parent_id',
    ];

    protected $casts = [
        'status' => 'boolean'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function getKeywords()
    {
        $keywords_list = $this->keywords;
        $keywords = explode(',', $keywords_list);
        return $keywords;
    }
}
