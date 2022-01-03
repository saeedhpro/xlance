<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;
    public $timestamps = false;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'marital_status' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $fillable = [
        'position',
        'gender',
        'bio',
        'description',
        'marital_status',
        'birth_date',
        'languages',
        'avatar_id',
        'new_avatar_id',
        'bg_id',
        'new_bg_id',
        'national_card_id',
        'new_national_card_id',
        'national_card_accepted',
        'sheba_accepted',
        'sheba',
        'avatar_accepted',
        'bg_accepted',
        'user_id',
    ];

    public function getLanguages()
    {
        $languages_list = $this->languages;
        if(!empty($languages_list)) {
            $languages = explode(',', $languages_list);
        } else {
            $languages = array();
        }
        return $languages;
    }

    public function avatar()
    {
        return $this->morphOne(Image::class, 'imageable', 'imageable_type', 'id', 'avatar_id');
    }

    public function newAvatar()
    {
        return $this->morphOne(Image::class, 'imageable', 'imageable_type', 'id', 'new_avatar_id');
    }

    public function background()
    {
        return $this->morphOne(Image::class, 'imageable', 'imageable_type', 'id', 'bg_id');
    }

    public function newBackground()
    {
        return $this->morphOne(Image::class, 'imageable', 'imageable_type', 'id', 'new_bg_id');
    }

    public function nationalCard()
    {
        return $this->morphOne(Image::class, 'imageable', 'imageable_type', 'id', 'national_card_id');
    }

    public function newNationalCard()
    {
        return $this->morphOne(Image::class, 'imageable', 'imageable_type', 'id', 'new_national_card_id');
    }

}
