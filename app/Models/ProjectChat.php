<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectChat extends Model
{
    use HasFactory;

    public function conversation () {
        return $this->morphOne(Conversation::class, 'conversationable');
    }

    public function creator() {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }

    public function reciever() {
        return $this->belongsTo(User::class, 'reciever_id', 'id');
    }

    public function project() {
        return $this->belongsTo(Project::class);
    }
}
