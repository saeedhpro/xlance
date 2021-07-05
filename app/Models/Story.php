<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Story extends Model
{
    use HasFactory;

    protected $fillable = [
        'caption',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function media()
    {
        return $this->morphOne(Media::class, 'mediable');
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }
}
