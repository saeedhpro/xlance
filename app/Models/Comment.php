<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'body',
        'commentable_type',
        'commentable_id',
        'user_id',
        'parent_id'
    ];

    public function commentable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function repliedTo()
    {
        return $this->belongsTo(Comment::class, 'parent_id', 'id');
    }

    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id', 'id');
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }
}
