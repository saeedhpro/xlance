<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'upload_id',
        'type',
        'conversation_id',
        'is_system',
        'body',
        'seen'
    ];

    const TEXT_TYPE = 'text';
    const FILE_TYPE = 'file';

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function conversation() {
        return $this->belongsTo(Conversation::class);
    }

    public function file() {
        return $this->belongsTo(Upload::class, 'upload_id', 'id');
    }
}
