<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        'title',
        'event_name',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
