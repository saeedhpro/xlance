<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Education extends Model
{
    use HasFactory;
    protected $table = 'educations';
    public $timestamps = false;
    protected $fillable = [
        'school_name',
        'degree',
        'user_id',
        'from_date',
        'to_date',
        'up_to_now',
        'description',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
