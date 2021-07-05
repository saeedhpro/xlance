<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Portfolio extends Model
{
    use HasFactory;

    const CREATED_STATUS = 'created';
    const ACCEPTED_STATUS = 'accepted';
    const REJECTED_STATUS = 'rejected';

    protected $fillable = [
        'title',
        'status',
        'description',
        'tags',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class);
    }

    public function attachments()
    {
        return $this->hasMany(PortfolioAttachment::class);
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    public function getTags()
    {
        $tags = $this->tags;
        return Str::length($tags) > 0 ? explode(',', $this->tags) : [];
    }
}
