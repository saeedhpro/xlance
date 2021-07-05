<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Media extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'media';

    const IMAGE_TYPE = 'image';
    const VIDEO_TYPE = 'video';

    protected $fillable = [
        'type',
        'name',
        'path',
        'mediable_id',
        'mediable_type',
    ];

    public function mediable()
    {
        return $this->morphTo();
    }
}
