<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory;
    protected $table = 'rating';
    protected $fillable = [
        'rate',
        'description',
        'user_id',
        'rater_id',
        'project_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function rater()
    {
        return $this->belongsTo(User::class, 'rater_id', 'id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
