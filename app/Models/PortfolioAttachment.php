<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortfolioAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'path',
        'project_id',
        'user_id'
    ];

    public function portfolio()
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
