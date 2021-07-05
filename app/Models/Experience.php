<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Experience extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $fillable = [
        'position',
        'company',
        'description',
        'up_to_now',
        'from_date',
        'to_date',
        'user_id'
    ];

    protected $casts = [
        'up_to_now' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
