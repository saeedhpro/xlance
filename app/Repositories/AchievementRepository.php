<?php


namespace App\Repositories;


use App\Interfaces\AchievementInterface;
use Illuminate\Database\Eloquent\Model;

class AchievementRepository extends BaseRepository implements AchievementInterface
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
        parent::__construct($model);
    }
}
