<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisputeMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'dispute_id',
        'body',
        'sender_id',
    ];

    public function dispute()
    {
        return $this->belongsTo(Dispute::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id', 'id');
    }
}
