<?php


namespace App\Repositories;


use App\Interfaces\ExperienceInterface;
use Illuminate\Database\Eloquent\Model;

class ExperienceRepository extends BaseRepository implements ExperienceInterface
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
        parent::__construct($model);
    }
}
