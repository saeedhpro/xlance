<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Overtrue\LaravelFavorite\Traits\Favoriteable;
use Overtrue\LaravelLike\Traits\Likeable;

class Post extends Model
{
    use HasFactory, SoftDeletes, Likeable, Favoriteable;

    protected $fillable = [
        'caption',
        'lat',
        'long',
        'user_id',
    ];

    public function media()
    {
        return $this->morphOne(Media::class, 'mediable');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function liked()
    {
        /** @var User $auth */
        $auth = auth()->user();
        if($auth) {
            return $auth->hasLiked($this);
        } else {
            return false;
        }
    }

    public function savedPost()
    {
        /** @var User $auth */
        $auth = auth()->user();
        if($auth) {
            return $auth->hasFavorited($this);
        } else {
            return false;
        }
    }

    public function marked()
    {
        /** @var User $auth */
        $auth = auth()->user();
        if($auth) {
            return $auth->hasBookmarked($this);
        } else {
            return false;
        }
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }
}
